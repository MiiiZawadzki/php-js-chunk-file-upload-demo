<?php
try {
    // creating required directories
    $baseChunksDir = __DIR__ . '/chunks/';
    $uploadsDir = __DIR__ . '/uploads/';

    if (!is_dir($baseChunksDir)) mkdir($baseChunksDir, 0777, true);
    if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0777, true);

    // validate data from the request
    $uploadId = $_POST['upload_id'] ?? null;
    $filename = $_POST['filename'] ?? null;
    $fileHash = $_POST['fileHash'] ?? null;
    $chunkIndex = $_POST['chunkIndex'] ?? null;
    $totalChunks = $_POST['totalChunks'] ?? null;
    $file = $_FILES['chunk'] ?? null;

    if (!$uploadId || !$filename || !$fileHash || $chunkIndex === null || !$totalChunks || !$file) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required data!']);
        exit;
    }

    // create directory for the chunks upload
    $uploadChunkDir = $baseChunksDir . basename($uploadId) . '/';
    if (!is_dir($uploadChunkDir)) mkdir($uploadChunkDir, 0777, true);

    $chunkPath = "{$uploadChunkDir}" . basename($filename) . ".part{$chunkIndex}";

    // upload the chunk
    if (!move_uploaded_file($file['tmp_name'], $chunkPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Nie udało się zapisać chunku.']);
        exit;
    }

    // check if all chunks are received
    $allChunksReceived = true;
    for ($i = 0; $i < $totalChunks; $i++) {
        if (!file_exists("{$uploadChunkDir}{$filename}.part{$i}")) {
            $allChunksReceived = false;
            break;
        }
    }

    $currentChunk = $chunkIndex + 1;
    $message = "Chunk $currentChunk / $totalChunks saved.";

    if ($allChunksReceived) {
        // create directory for the final file
        $uploadFilePath = $uploadsDir . basename($uploadId) . '/';
        if (!is_dir($uploadFilePath)) mkdir($uploadFilePath, 0777, true);

        $finalPath = $uploadFilePath . basename($filename);
        $output = fopen($finalPath, 'wb');

        // merge all chunks into the final file
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkFilePath = "{$uploadChunkDir}{$filename}.part{$i}";
            $chunkFile = fopen($chunkFilePath, 'rb');
            stream_copy_to_stream($chunkFile, $output);
            fclose($chunkFile);
        }

        fclose($output);

        // delete the chunks after merging
        deleteChunks($uploadChunkDir, $filename, $totalChunks);

        // verify the file hash
        $calculatedHash = hash_file('sha256', $finalPath);

        if ($calculatedHash !== $fileHash) {
            unlink($finalPath);

            http_response_code(400);
            echo json_encode(['message' => 'ERROR: Hash mismatch. File integrity check failed.']);
            exit;
        }

        $message .= "\nFile saved successfully with verified hash: $calculatedHash.";
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => true, 'message' => $message]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

function deleteChunks(string $uploadChunkDir, string $filename, int $totalChunks): void
{
    for ($i = 0; $i < $totalChunks; $i++) {
        $chunkFilePath = "{$uploadChunkDir}{$filename}.part{$i}";
        if (file_exists($chunkFilePath)) {
            unlink($chunkFilePath);
        }
    }

    if (is_dir($uploadChunkDir)) {
        rmdir($uploadChunkDir);
    }
}
