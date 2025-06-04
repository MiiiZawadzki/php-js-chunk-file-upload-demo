document
  .getElementById("upload-form")
  .addEventListener("submit", async function (e) {
    e.preventDefault();

    const uploadId = crypto.randomUUID();

    const fileInput = document.getElementById("file-upload");
    const chunkText = document.getElementById("chunk");
    const segments = document.querySelectorAll(".chunk-segment");

    if (!fileInput.files.length)
      return alert("Please select a file to upload.");

    const file = fileInput.files[0];
    const fileHash = await calculateSHA256(file);
    const chunkSize = 1024 * 1024;
    let start = 0;
    const totalChunks = Math.ceil(file.size / chunkSize);
    let currentChunk = 0;

    chunkText.textContent = currentChunk;

    while (start < file.size) {
      await uploadChunk(file.slice(start, start + chunkSize), currentChunk);

      start += chunkSize;
      currentChunk += 1;
    }

    async function uploadChunk(chunk, chunkIndex) {
      console.log("Uploading chunk:", chunk);

      const formData = new FormData();
      formData.append("upload_id", uploadId);
      formData.append("filename", file.name);
      formData.append("chunkIndex", chunkIndex);
      formData.append("totalChunks", totalChunks);
      formData.append("chunk", chunk, file.name);
      formData.append("fileHash", fileHash);

      try {
        const response = await fetch("server.php", {
          method: "POST",
          body: formData,
        });

        const result = await response.json();

        logMessage(result.message);
        segments[chunkIndex].classList.add("chunk-completed");
        chunkText.textContent = chunkIndex + 1;
      } catch (err) {
        console.error(`Error on Chunk: ${chunkIndex}`, err);
        return;
      }
    }

    function logMessage(message) {
      const logContainer = document.getElementById("log-container");
      const logMessage = document.createElement("p");
      logMessage.className = "log-message";
      logMessage.textContent = `${message}`;
      logContainer.appendChild(logMessage);
      logContainer.scrollTop = logContainer.scrollHeight;
    }
  });

document
  .getElementById("file-upload")
  .addEventListener("change", async function () {
    document.getElementById("chunk").textContent = "0";
    const progressContainer = document.getElementById("progress-container");
    progressContainer.innerHTML = "";

    const chunkSize = 1024 * 1024;
    const file = this.files[0];
    const fileName = document.getElementById("file-name");
    const fileSize = document.getElementById("file-size");
    const fileHash = document.getElementById("file-hash");
    const totalChunksText = document.getElementById("total-chunks");

    if (file) {
      const sizeMB = (file.size / chunkSize).toFixed(2);
      const totalChunks = Math.ceil(file.size / chunkSize);
      const hash = await calculateSHA256(file);

      fileName.textContent = file.name;
      fileHash.textContent = hash;
      fileSize.textContent = `${sizeMB} MB`;
      totalChunksText.textContent = totalChunks;

      for (let i = 0; i < totalChunks; i++) {
        const segment = document.createElement("div");
        segment.classList.add("chunk-segment");
        progressContainer.appendChild(segment);
      }
    } else {
      fileName.textContent = "-/-";
      fileSize.textContent = "-/-";
    }
  });

async function calculateSHA256(file) {
  const arrayBuffer = await file.arrayBuffer();
  const hashBuffer = await crypto.subtle.digest("SHA-256", arrayBuffer);
  const hashArray = Array.from(new Uint8Array(hashBuffer));
  const hashHex = hashArray
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
  return hashHex;
}
