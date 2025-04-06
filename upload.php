<?php
session_start();
require 'db.php';

// Create uploads directory if it doesn't exist
if (!file_exists('uploads')) {
    mkdir('uploads', 0755, true);
}

$operation = $_POST['operation'] ?? '';

// Basic validation for operation
if (empty($operation)) {
    showError("No operation selected.");
    exit;
}

// Handle file upload based on operation
if ($operation === 'merge') {
    if (!isset($_FILES['pdfs']) || empty($_FILES['pdfs']['name'][0])) {
        showError("Please select at least 2 PDF files to merge.");
        exit;
    }
    
    $files = [];
    $fileIds = [];
    $fileCount = count($_FILES['pdfs']['name']);
    
    if ($fileCount < 2) {
        showError("Please select at least 2 PDF files to merge.");
        exit;
    }
    
    // Process each file
    for ($i = 0; $i < $fileCount; $i++) {
        $fileData = [
            'name' => $_FILES['pdfs']['name'][$i],
            'type' => $_FILES['pdfs']['type'][$i],
            'tmp_name' => $_FILES['pdfs']['tmp_name'][$i],
            'error' => $_FILES['pdfs']['error'][$i],
            'size' => $_FILES['pdfs']['size'][$i]
        ];
        
        $file = processUploadedFile($fileData);
        if ($file) {
            $files[] = $file;
            // Store in database and get ID
            $fileId = DB::storeDocument($fileData, 'upload');
            if ($fileId) {
                $fileIds[] = $fileId;
            }
        } else {
            // Clean up any already uploaded files
            foreach ($files as $uploadedFile) {
                if (file_exists($uploadedFile)) {
                    unlink($uploadedFile);
                }
            }
            exit;
        }
    }
    
    // Store files and IDs in session for processing
    $_SESSION['uploaded_files'] = $files;
    $_SESSION['uploaded_file_ids'] = $fileIds;
    header("Location: process.php?operation=" . urlencode($operation));
} else {
    // Single file operations
    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] != 0) {
        showError("Upload failed. Error code: " . ($_FILES['pdf']['error'] ?? 'Unknown'));
        exit;
    }

    $file = processUploadedFile($_FILES['pdf']);
    if (!$file) {
        exit;
    }

    // Store in database and get ID
    $fileId = DB::storeDocument($_FILES['pdf'], 'upload');
    $_SESSION['uploaded_file_id'] = $fileId;

    // Add parameters for split operation or convert operation
    $additionalParams = '';
    if ($operation === 'split' && isset($_POST['startPage']) && isset($_POST['endPage'])) {
        $additionalParams = "&startPage=" . urlencode($_POST['startPage']) . 
                         "&endPage=" . urlencode($_POST['endPage']);
    } else if ($operation === 'convert' && isset($_POST['convertPage'])) {
        $additionalParams = "&convertPage=" . urlencode($_POST['convertPage']);
    }

    header("Location: process.php?file=" . urlencode($file) . "&operation=" . urlencode($operation) . $additionalParams);
}

function processUploadedFile($fileData) {
    // Check if it's actually a PDF
    $fileName = $fileData['name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if ($fileExt != 'pdf') {
        showError("Invalid file type. Only PDF files are accepted.");
        return false;
    }
    
    // Validate file size (10MB limit)
    $maxSize = 10 * 1024 * 1024; // 10MB
    if ($fileData['size'] > $maxSize) {
        showError("File is too large. Maximum size is 10MB.");
        return false;
    }
    
    // Create safe filename
    $safeFilename = "uploads/" . time() . "_" . uniqid() . "_" . preg_replace("/[^a-zA-Z0-9._-]/", "", basename($fileData['name']));
    
    // Move uploaded file
    if (move_uploaded_file($fileData['tmp_name'], $safeFilename)) {
        return $safeFilename;
    } else {
        showError("Could not save the uploaded file. Please check directory permissions.");
        return false;
    }
}

function showError($message) {
    echo '<!DOCTYPE html>
<html>
<head>
    <title>PDF Toolkit - Error</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="error-container">
        <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h2>Upload Error</h2>
        <div class="message">
            <p>' . htmlspecialchars($message) . '</p>
        </div>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Toolkit</a>
    </div>
</body>
</html>';
}