<?php
session_start();
require_once 'db.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video - YouTubeClone</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', Arial, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: #0a0a0a;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: white;
            box-shadow: 0 1px 10px rgba(0,0,0,0.1);
        }
        
        .logo {
            display: flex;
            align-items: center;
            color: #ff0000;
            text-decoration: none;
            font-size: 22px;
            font-weight: bold;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 28px;
        }
        
        .upload-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .upload-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .upload-header h1 {
            font-size: 24px;
            color: #0a0a0a;
            margin-bottom: 10px;
        }
        
        .upload-header p {
            color: #606060;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #606060;
            font-size: 14px;
        }
        
        .form-group input[type="text"], 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input[type="text"]:focus, 
        .form-group textarea:focus {
            border-color: #1a73e8;
            outline: none;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .file-upload {
            display: block;
            width: 100%;
            padding: 40px 20px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .file-upload:hover {
            border-color: #1a73e8;
            background-color: #f8f9fa;
        }
        
        .file-upload i {
            font-size: 40px;
            color: #606060;
            margin-bottom: 10px;
        }
        
        .file-upload p {
            color: #606060;
            margin-bottom: 5px;
        }
        
        .file-upload span {
            font-size: 12px;
            color: #999;
        }
        
        #file-selected {
            font-size: 14px;
            color: #1a73e8;
            margin-top: 10px;
            display: none;
        }
        
        #upload-progress {
            width: 100%;
            background-color: #f1f1f1;
            height: 20px;
            border-radius: 10px;
            margin-top: 20px;
            overflow: hidden;
            display: none;
        }
        
        #progress-bar {
            width: 0%;
            height: 100%;
            background-color: #4CAF50;
            border-radius: 10px;
            transition: width 0.3s;
        }
        
        #progress-text {
            text-align: center;
            margin-top: 5px;
            font-size: 14px;
            color: #606060;
        }
        
        .cancel-btn {
            background-color: #f1f1f1;
            color: #606060;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        
        .upload-btn {
            background-color: #ff0000;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .buttons {
            display: flex;
            justify-content: flex-end;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }
        
        .success-message {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            display: none;
        }
        
        .home-link {
            color: #606060;
            text-decoration: none;
            display: flex;
            align-items: center;
            font-size: 16px;
            margin-left: auto;
        }
        
        .home-link i {
            margin-right: 5px;
        }
        
        .home-link:hover {
            color: #ff0000;
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="logo">
            <i class="fab fa-youtube"></i>
            <span>YouTubeClone</span>
        </a>
        
        <a href="index.php" class="home-link">
            <i class="fas fa-home"></i> Back to Home
        </a>
    </header>
    
    <div class="upload-container">
        <div class="upload-header">
            <h1>Upload a Video</h1>
            <p>Share your video with the world!</p>
        </div>
        
        <div id="error-message" class="error-message"></div>
        <div id="success-message" class="success-message"></div>
        
        <form id="upload-form" action="process_upload.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Video Title</label>
                <input type="text" id="title" name="title" placeholder="Add a title that describes your video" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" placeholder="Tell viewers about your video (optional)"></textarea>
            </div>
            
            <div class="form-group">
                <label for="video">Video File (Max: 1000MB)</label>
                <div class="file-upload" id="drop-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Drag and drop your video here or click to browse</p>
                    <span>MP4, AVI, MOV, MKV formats supported (Max 1000MB)</span>
                    <input type="file" id="video" name="video" accept="video/*" style="display: none;" required>
                </div>
                <div id="file-selected"></div>
                
                <div id="upload-progress">
                    <div id="progress-bar"></div>
                </div>
                <div id="progress-text"></div>
            </div>
            
            <div class="buttons">
                <button type="button" class="cancel-btn" onclick="location.href='index.php'">Cancel</button>
                <button type="submit" class="upload-btn">Upload</button>
            </div>
        </form>
    </div>
    
    <script>
        // Handle file input
        const dropArea = document.getElementById('drop-area');
        const fileInput = document.getElementById('video');
        const fileSelected = document.getElementById('file-selected');
        const errorMessage = document.getElementById('error-message');
        const uploadForm = document.getElementById('upload-form');
        const progressBar = document.getElementById('progress-bar');
        const progressContainer = document.getElementById('upload-progress');
        const progressText = document.getElementById('progress-text');
        const successMessage = document.getElementById('success-message');
        
        // Click to select file
        dropArea.addEventListener('click', () => {
            fileInput.click();
        });
        
        // Display selected file name
        fileInput.addEventListener('change', function() {
            if(this.files.length > 0) {
                const file = this.files[0];
                // Check file size (1000MB = 1000 * 1024 * 1024 bytes)
                if(file.size > 1000 * 1024 * 1024) {
                    errorMessage.textContent = "File size exceeds 1000MB limit.";
                    errorMessage.style.display = "block";
                    this.value = '';
                    fileSelected.style.display = "none";
                    return;
                }
                
                fileSelected.textContent = `Selected file: ${file.name}`;
                fileSelected.style.display = "block";
                errorMessage.style.display = "none";
            }
        });
        
        // Drag and Drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            dropArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            dropArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            dropArea.style.borderColor = '#1a73e8';
            dropArea.style.backgroundColor = '#f8f9fa';
        }
        
        function unhighlight() {
            dropArea.style.borderColor = '#ddd';
            dropArea.style.backgroundColor = 'white';
        }
        
        dropArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const file = dt.files[0];
            
            if(file.size > 1000 * 1024 * 1024) {
                errorMessage.textContent = "File size exceeds 1000MB limit.";
                errorMessage.style.display = "block";
                return;
            }
            
            fileInput.files = dt.files;
            fileSelected.textContent = `Selected file: ${file.name}`;
            fileSelected.style.display = "block";
            errorMessage.style.display = "none";
        }
        
        // Form submission with AJAX
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if(!fileInput.files.length) {
                errorMessage.textContent = "Please select a video file.";
                errorMessage.style.display = "block";
                return;
            }
            
            const title = document.getElementById('title').value.trim();
            if(!title) {
                errorMessage.textContent = "Please enter a video title.";
                errorMessage.style.display = "block";
                return;
            }
            
            // Using FormData to handle file uploads
            const formData = new FormData(this);
            
            // Show progress bar
            progressContainer.style.display = "block";
            
            // AJAX request to upload the file
            const xhr = new XMLHttpRequest();
            
            xhr.open('POST', 'process_upload.php', true);
            
            xhr.upload.onprogress = function(e) {
                if(e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                    progressText.textContent = Math.round(percentComplete) + '% uploaded...';
                }
            };
            
            xhr.onload = function() {
                if(xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if(response.status === 'success') {
                            successMessage.textContent = response.message;
                            successMessage.style.display = "block";
                            errorMessage.style.display = "none";
                            
                            // Redirect after successful upload
                            setTimeout(function() {
                                window.location.href = 'watch.php?id=' + response.video_id;
                            }, 2000);
                        } else {
                            errorMessage.textContent = response.message;
                            errorMessage.style.display = "block";
                            successMessage.style.display = "none";
                            progressContainer.style.display = "none";
                        }
                    } catch(e) {
                        errorMessage.textContent = "Error processing response.";
                        errorMessage.style.display = "block";
                        progressContainer.style.display = "none";
                    }
                } else {
                    errorMessage.textContent = "Upload failed. Please try again.";
                    errorMessage.style.display = "block";
                    progressContainer.style.display = "none";
                }
            };
            
            xhr.onerror = function() {
                errorMessage.textContent = "Network error. Please try again.";
                errorMessage.style.display = "block";
                progressContainer.style.display = "none";
            };
            
            xhr.send(formData);
        });
    </script>
</body>
</html>
