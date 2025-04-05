<?php
session_start();
require 'db.php';
require 'libs/pdf-tools.php';

// Create output directory if it doesn't exist
if (!file_exists('output')) {
    mkdir('output', 0755, true);
}

$file = $_GET['file'] ?? '';
$operation = $_GET['operation'] ?? '';
$files = [];

if (empty($operation)) {
    outputError("No operation specified.");
    exit;
}

// For merge operation, get files from session
if ($operation === 'merge') {
    $files = $_SESSION['uploaded_files'] ?? [];
    if (empty($files)) {
        outputError("No files found for merging. Please try again.");
        exit;
    }
    unset($_SESSION['uploaded_files']);
} elseif (empty($file) || !file_exists($file)) {
    outputError("Invalid file path or file not found.");
    exit;
}

// Check if the PDF is compatible with FPDI for operations that need it
$checkFile = ($operation === 'merge') ? $files[0] : $file;
if (($operation == 'merge' || $operation == 'split') && !isPdfCompatible($checkFile)) {
    outputError("
        <h3>Unsupported PDF Format</h3>
        <p>This PDF uses a compression technique not supported by the free FPDI parser.</p>
        <p>Options:</p>
        <ul>
            <li>Try a different PDF file</li>
            <li>Use the compress option which uses GhostScript instead</li>
            <li>Consider purchasing the <a href='https://www.setasign.com/fpdi-pdf-parser' target='_blank'>FPDI PDF Parser</a></li>
        </ul>
    ");
    exit;
}

$success = false;
$resultFile = '';
$resultMessage = '';

switch ($operation) {
    case 'merge':
        $outputFile = tempnam(sys_get_temp_dir(), 'merged_') . '.pdf';
        $success = mergePDFs($files, $outputFile);
        
        if ($success) {
            $resultMessage = count($files) . " PDFs successfully merged!";
            
            // Store result in database
            $outputDocId = DB::storeDocument([
                'name' => 'merged_' . time() . '.pdf',
                'type' => 'application/pdf',
                'tmp_name' => $outputFile,
                'size' => filesize($outputFile),
                'error' => 0
            ], $operation);
            
            // Record operation
            DB::storeOperation($operation, $fileIds, $outputDocId);
            
            $resultFile = 'download.php?id=' . $outputDocId;
        }
        
        // Clean up temp files
        foreach ($files as $f) @unlink($f);
        @unlink($outputFile);
        break;

    case 'split':
        $startPage = isset($_GET['startPage']) ? (int)$_GET['startPage'] : 1;
        $endPage = isset($_GET['endPage']) ? (int)$_GET['endPage'] : null;
        
        $success = splitPDF($file, $startPage, $endPage);
        
        if ($success) {
            // Get all split files that were created
            $outputFiles = glob(sys_get_temp_dir() . '/split_*.pdf');
            usort($outputFiles, function($a, $b) {
                preg_match('/split_(\d+)\.pdf/', $a, $matchesA);
                preg_match('/split_(\d+)\.pdf/', $b, $matchesB);
                $numA = isset($matchesA[1]) ? (int)$matchesA[1] : 0;
                $numB = isset($matchesB[1]) ? (int)$matchesB[1] : 0;
                return $numA - $numB;
            });
            
            $fileCount = count($outputFiles);
            $resultMessage = "PDF successfully split into $fileCount pages!";
    
            if ($fileCount > 1) {
                // Create a ZIP file containing all the split PDFs
                $zipFile = tempnam(sys_get_temp_dir(), 'split_') . '.zip';
                $zip = new ZipArchive();
                if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    foreach ($outputFiles as $splitFile) {
                        $zip->addFile($splitFile, basename($splitFile));
                    }
                    $zip->close();
                    
                    // Store ZIP in database
                    $zipDocId = DB::storeDocument([
                        'name' => 'split_pages_' . time() . '.zip',
                        'type' => 'application/zip',
                        'tmp_name' => $zipFile,
                        'size' => filesize($zipFile),
                        'error' => 0
                    ], $operation);
                    
                    // Record operation
                    DB::storeOperation($operation, [$fileId], $zipDocId, [
                        'startPage' => $startPage,
                        'endPage' => $endPage,
                        'pageCount' => $fileCount
                    ]);
                    
                    $resultFile = 'download.php?id=' . $zipDocId;
                    $resultMessage .= " A ZIP file with all pages has been created.";
                }
            }
            
            // Clean up temp files
            foreach ($outputFiles as $f) @unlink($f);
            @unlink($zipFile);
        }
        break;

    case 'compress':
        $resultFile = 'output/compressed_' . time() . '.pdf';
        $success = compressPDF($file, $resultFile);
        if ($success) {
            $originalSize = filesize($file);
            $compressedSize = filesize($resultFile);
            $savings = round(($originalSize - $compressedSize) / $originalSize * 100, 1);
            $resultMessage = "PDF successfully compressed! Size reduced by $savings% (From " . 
                formatBytes($originalSize) . " to " . formatBytes($compressedSize) . ")";
        } else {
            outputError("Failed to compress PDF. Make sure GhostScript is installed on the server.");
            exit;
        }
        break;

        case 'convert':
            $pageNumber = isset($_GET['convertPage']) ? (int)$_GET['convertPage'] : 1;
            $resultFile = 'output/image_' . time() . '.png';
            $success = convertPDFToImage($file, $resultFile, $pageNumber);
            if ($success) {
                $resultMessage = "PDF page $pageNumber successfully converted to image!";
            } else {
                outputError("Failed to convert PDF to image. Make sure ImageMagick is installed on the server.");
                exit;
            }
            break;

        case 'pdf-to-word':
                $resultFile = 'output/converted_' . time() . '.docx';
                $success = convertPDFToWord($file, $resultFile);
                if ($success) {
                    $resultMessage = "PDF successfully converted to Word document!";
                } else {
                    outputError("Failed to convert PDF to Word. Make sure LibreOffice or OpenOffice is installed on the server.");
                    exit;
                }
                break;

    default:
        outputError("Unknown operation: $operation");
        exit;
}

if ($success) {
    outputSuccess($resultMessage, $resultFile, $operation);
}

/**
 * Display error message with consistent formatting
 */
function outputError($message) {
    echo '<!DOCTYPE html>
<html>
<head>
    <title>PDF Toolkit - Error</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f7fa;
            color: #333;
        }
        .error-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-top: 30px;
            border-left: 5px solid #ef476f;
        }
        h2 {
            color: #ef476f;
            margin-top: 0;
        }
        .icon {
            font-size: 3rem;
            color: #ef476f;
            margin-bottom: 20px;
            text-align: center;
        }
        .back-btn {
            display: inline-block;
            background-color: #4361ee;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            font-weight: bold;
        }
        .back-btn:hover {
            background-color: #3f37c9;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
        <h2>Processing Error</h2>
        <div class="message">' . $message . '</div>
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Toolkit</a>
    </div>
</body>
</html>';
}

/**
 * Display success message with download link
 */
function outputSuccess($message, $resultFile, $operation) {
    $fileType = pathinfo($resultFile, PATHINFO_EXTENSION);
    $iconClass = 'fa-file-pdf';

    if (in_array($fileType, ['png', 'jpg', 'jpeg'])) {
        $iconClass = 'fa-file-image';
    } else if ($fileType === 'zip') {
        $iconClass = 'fa-file-archive';
    }

    echo '<!DOCTYPE html>
<html>
<head>
    <title>PDF Toolkit - Success</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f7fa;
            color: #333;
        }
        .success-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-top: 30px;
            border-left: 5px solid #06d6a0;
            text-align: center;
        }
        h2 {
            color: #06d6a0;
            margin-top: 0;
        }
        .icon {
            font-size: 3rem;
            color: #06d6a0;
            margin-bottom: 20px;
        }
        .message {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }
        .download-btn {
            display: inline-block;
            background-color: #4361ee;
            color: white;
            padding: 15px 25px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
            font-size: 1.1rem;
            font-weight: bold;
        }
        .download-btn:hover {
            background-color: #3f37c9;
        }
        .back-btn {
            display: inline-block;
            background-color: #adb5bd;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .back-btn:hover {
            background-color: #6c757d;
        }
        .file-preview {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            text-align: center;
        }
        .file-icon {
            font-size: 2.5rem;
            color: #4361ee;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="icon"><i class="fas fa-check-circle"></i></div>
        <h2>Success!</h2>
        <div class="message">' . $message . '</div>';

    if ($operation === 'convert' && file_exists($resultFile)) {
        echo '<div class="file-preview">
            <img src="' . $resultFile . '" alt="PDF Preview" style="max-width: 100%; max-height: 300px;">
        </div>';
    } else {
        echo '<div class="file-preview">
            <div class="file-icon"><i class="fas ' . $iconClass . '"></i></div>
            <div>Your file is ready for download</div>
        </div>';
    }

    echo '<a href="' . $resultFile . '" class="download-btn" download><i class="fas fa-download"></i> Download Result</a>
        <div><a href="index.php" class="back-btn"><i class="fas fa-home"></i> Back to Toolkit</a></div>
    </div>
</body>
</html>';
}

/**
 * Format bytes to human-readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}