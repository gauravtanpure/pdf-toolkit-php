<?php
require 'vendor/autoload.php'; // Using Composer
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;

/**
 * Check if the PDF is compatible with FPDI
 * 
 * @param string $file PDF file path
 * @return bool True if compatible, false otherwise
 */
function isPdfCompatible($file) {
    try {
        $pdf = new FPDI();
        $pageCount = $pdf->setSourceFile($file);
        return $pageCount > 0;
    } catch (Exception $e) {
        error_log("PDF Compatibility Check Error: " . $e->getMessage());
        return strpos($e->getMessage(), 'not supported by the free parser') === false;
    }
}

/**
 * Merge multiple PDFs into one
 * 
 * @param array $files Array of PDF file paths
 * @param string $output Output file path
 * @return bool True on success, false on failure
 */
function mergePDFs($files, $output) {
    try {
        // Validate input files
        if (empty($files)) {
            error_log("PDF Merge Error: No input files provided");
            return false;
        }
        
        $pdf = new FPDI();
        
        foreach ($files as $file) {
            if (!file_exists($file)) {
                error_log("PDF Merge Error: File not found - $file");
                continue;
            }
            
            try {
                $pageCount = $pdf->setSourceFile($file);
                
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tpl = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tpl);
                    
                    // Add page with correct orientation
                    if ($size['width'] > $size['height']) {
                        $pdf->AddPage('L', [$size['width'], $size['height']]);
                    } else {
                        $pdf->AddPage('P', [$size['width'], $size['height']]);
                    }
                    
                    $pdf->useTemplate($tpl);
                }
            } catch (Exception $e) {
                error_log("PDF Merge Error with file $file: " . $e->getMessage());
                continue;
            }
        }
        
        // Create output directory if it doesn't exist
        $outputDir = dirname($output);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Only save if we have at least one page
        if ($pdf->PageNo() > 0) {
            $pdf->Output($output, 'F');
            return file_exists($output);
        } else {
            error_log("PDF Merge Error: No pages were added");
            return false;
        }
    } catch (Exception $e) {
        error_log("PDF Merge Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Split a PDF into separate pages based on specified range
 * 
 * @param string $file PDF file path
 * @param int $startPage Starting page number (optional)
 * @param int $endPage Ending page number (optional)
 * @return bool True on success, false on failure
 */
function splitPDF($file, $startPage = null, $endPage = null) {
    try {
        // Clean up any existing split files from previous operations
        $existingSplitFiles = glob('output/split_*.pdf');
        foreach ($existingSplitFiles as $oldFile) {
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }
        
        // Create the output directory if it doesn't exist
        if (!is_dir('output')) {
            mkdir('output', 0755, true);
        }
        
        // First, check if we can open the file
        $pdf = new FPDI();
        $pageCount = $pdf->setSourceFile($file);
        
        if ($pageCount < 1) {
            error_log("PDF Split Error: No pages found in the document");
            return false;
        }
        
        // Validate page range
        $startPage = ($startPage !== null && $startPage > 0) ? min($startPage, $pageCount) : 1;
        $endPage = ($endPage !== null && $endPage > 0) ? min($endPage, $pageCount) : $pageCount;
        
        if ($startPage > $endPage) {
            error_log("PDF Split Error: Start page ($startPage) is greater than end page ($endPage)");
            return false;
        }
        
        $success = false;
        
        // Create a separate PDF for each page in the range
        for ($pageNumber = $startPage; $pageNumber <= $endPage; $pageNumber++) {
            try {
                $newPdf = new FPDI();
                $templateId = $newPdf->setSourceFile($file); // Open the source file again
                
                if ($templateId < 1) {
                    error_log("PDF Split Error: Could not open source file for page $pageNumber");
                    continue;
                }
                
                // Import the page
                $template = $newPdf->importPage($pageNumber);
                $size = $newPdf->getTemplateSize($template);
                
                // Add page with the correct orientation
                if ($size['width'] > $size['height']) {
                    $newPdf->AddPage('L', [$size['width'], $size['height']]);
                } else {
                    $newPdf->AddPage('P', [$size['width'], $size['height']]);
                }
                
                // Use the imported page
                $newPdf->useTemplate($template);
                
                // Generate output filename with leading zeros for proper sorting
                $outputIndex = $pageNumber - $startPage + 1;
                $outputFile = sprintf('output/split_%03d.pdf', $outputIndex);
                
                // Save the PDF
                $newPdf->Output($outputFile, 'F');
                
                if (file_exists($outputFile)) {
                    $success = true;
                }
            } catch (Exception $e) {
                error_log("PDF Split Error on page $pageNumber: " . $e->getMessage());
                continue;
            }
        }
        
        return $success;
    } catch (CrossReferenceException $e) {
        error_log("PDF Split Error (CrossReference): " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("PDF Split Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Compress a PDF using GhostScript
 * 
 * @param string $file PDF file path
 * @param string $output Output file path
 * @return bool True on success, false on failure
 */
function compressPDF($file, $output) {
    try {
        // Check if GhostScript is available
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $gsCommand = $isWindows ? "gswin64c" : "gs";
        
        // Test if GhostScript is installed
        $testCommand = $isWindows ? "where $gsCommand 2>&1" : "which $gsCommand 2>&1";
        $gsCheck = shell_exec($testCommand);
        
        if (empty($gsCheck) || strpos($gsCheck, 'not found') !== false) {
            error_log("GhostScript not found. Cannot compress PDF.");
            return false;
        }
        
        // Create directory if it doesn't exist
        $outputDir = dirname($output);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Quote paths to handle spaces in filenames
        $file = escapeshellarg($file);
        $output = escapeshellarg($output);
        
        // Compression command
        $command = "$gsCommand -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/screen -dNOPAUSE -dQUIET -dBATCH -sOutputFile=$output $file 2>&1";
        
        $result = shell_exec($command);
        
        // Check if output file was created
        $outputPath = trim($output, "'\"");
        if (!file_exists($outputPath)) {
            error_log("GhostScript failed to create output. Result: $result");
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("PDF Compression Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Convert first page of PDF to image
 * 
 * @param string $file PDF file path
 * @param string $output Output image path
 * @return bool True on success, false on failure
 */
/**
 * Convert specified page of PDF to image
 * 
 * @param string $file PDF file path
 * @param string $output Output image path
 * @param int $pageNumber Page number to convert (default: 1)
 * @return bool True on success, false on failure
 */
function convertPDFToImage($file, $output, $pageNumber = 1) {
    try {
        // Ensure page number is valid
        $pageNumber = max(1, (int)$pageNumber);
        $pageIndex = $pageNumber - 1; // Convert to zero-based index
        
        // First try with ImageMagick PHP extension
        if (class_exists('Imagick')) {
            try {
                // Check if the requested page exists by counting pages first
                $pdfInfo = new Imagick();
                $pdfInfo->pingImage($file);
                $totalPages = $pdfInfo->getNumberImages();
                $pdfInfo->clear();
                
                if ($pageNumber > $totalPages) {
                    error_log("Page number $pageNumber exceeds total pages in PDF ($totalPages)");
                    return false;
                }
                
                $imagick = new Imagick();
                $imagick->setResolution(300, 300); // Higher resolution for better quality
                $imagick->readImage($file."[$pageIndex]"); // Read specified page
                $imagick->setImageFormat("png");
                $imagick->setImageCompression(Imagick::COMPRESSION_JPEG);
                $imagick->setImageCompressionQuality(90);
                
                // Create directory if it doesn't exist
                $outputDir = dirname($output);
                if (!is_dir($outputDir)) {
                    mkdir($outputDir, 0755, true);
                }
                
                // Save the image
                $imagick->writeImage($output);
                $imagick->clear();
                
                return file_exists($output);
            } catch (Exception $e) {
                error_log("Imagick Error: " . $e->getMessage());
                // Fall through to command line approach
            }
        }
        
        // Fallback to command line convert
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $convertCommand = $isWindows ? "magick convert" : "convert";
        
        // Test if convert is available
        $testCommand = $isWindows ? "where magick 2>&1" : "which convert 2>&1";
        $convertCheck = shell_exec($testCommand);
        
        if (empty($convertCheck) || strpos($convertCheck, 'not found') !== false) {
            error_log("ImageMagick convert command not found.");
            return false;
        }
        
        // Create directory if it doesn't exist
        $outputDir = dirname($output);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        // Quote paths to handle spaces in filenames
        $file = escapeshellarg($file);
        $output = escapeshellarg($output);
        
        // Use command line convert with the specified page index
        $command = "$convertCommand -density 300 {$file}[$pageIndex] -quality 90 $output 2>&1";
        $result = shell_exec($command);
        
        // Check if output file was created
        $outputPath = trim($output, "'\"");
        if (!file_exists($outputPath)) {
            error_log("Convert command failed. Result: $result");
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("PDF to Image Conversion Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Convert PDF to Word document using LibreOffice
 * 
 * @param string $file PDF file path
 * @param string $output Output DOCX file path
 * @return bool True on success, false on failure
 */
function convertPDFToWord($file, $output) {
    try {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        // Manually set the LibreOffice path if it's not in PATH
        $customSofficePath = 'D:\\Ghostscript\\LibreOffice\\program\\soffice.exe';

        // Check if custom soffice.exe exists
        if (!file_exists($customSofficePath)) {
            error_log("LibreOffice not found at $customSofficePath");
            return false;
        }

        // Create output directory if it doesn't exist
        $outputDir = dirname($output);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        // Create a temporary directory
        $tempDirRaw = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdf_to_word_' . time();
        if (!is_dir($tempDirRaw)) {
            mkdir($tempDirRaw, 0755, true);
        }

        // Escape paths
        $fileEscaped = escapeshellarg($file);
        $tempDirEscaped = escapeshellarg($tempDirRaw);

        // Use custom soffice path
        $sofficeCommand = escapeshellarg($customSofficePath);
        $command = "$sofficeCommand --headless --convert-to docx --outdir $tempDirEscaped $fileEscaped 2>&1";

        // Run the command
        $result = shell_exec($command);

        // Debug
        error_log("LibreOffice command: $command");
        error_log("LibreOffice output: $result");

        // Check output file
        $inputFileName = pathinfo($file, PATHINFO_FILENAME);
        $tempOutputFile = $tempDirRaw . DIRECTORY_SEPARATOR . $inputFileName . '.docx';

        if (!file_exists($tempOutputFile)) {
            error_log("Office conversion failed or output file not found.");
            cleanUpDir($tempDirRaw);
            return false;
        }

        // Move to final destination
        if (!rename($tempOutputFile, $output)) {
            copy($tempOutputFile, $output);
            unlink($tempOutputFile);
        }

        cleanUpDir($tempDirRaw);

        return file_exists($output);
    } catch (Exception $e) {
        error_log("PDF to Word Conversion Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up temporary directory
 */
function cleanUpDir($dir) {
    if (!is_dir($dir)) return;
    $files = scandir($dir);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            @unlink($dir . DIRECTORY_SEPARATOR . $f);
        }
    }
    @rmdir($dir);
}
