# PHP & JavaScript Chunk File Upload Demo

This is a demo application showcasing how to upload files in chunks using JavaScript on the frontend and PHP on the backend.

The application provides a simple form where you can select a file. Once selected, the file's metadata — including the name, size, and a generated checksum (hash) — will be displayed.

When you click the Upload button, the file is divided into 1 MB chunks, which are then uploaded sequentially via separate HTTP requests.

To help visualize the process, the app includes:

* A progress bar showing overall upload progress.

* A log viewer showing the status of each chunk upload.
  
![2](https://github.com/user-attachments/assets/f9c3d94c-0767-4e1f-922c-d33165bb0a44)


## Project Setup
To run the application locally:
1. Navigate to the project root directory.
2. Start a local PHP server:
```
  php -S localhost:8000
```
3. Open your browser and go to:
```
  http://localhost:8000/
```

After these steps, you should see the file upload form and be ready to test the chunk upload functionality.




