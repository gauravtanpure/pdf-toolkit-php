<?php
// Start session to store uploaded files temporarily
session_start();

// Clear previous uploads if any
if (isset($_SESSION['uploaded_files'])) {
    unset($_SESSION['uploaded_files']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Toolkit</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <h1><i class="fas fa-file-pdf"></i> PDF Toolkit</h1>
        <p class="subtitle">Simple and powerful tools for your PDF files</p>
    </header>
    
    <div class="card">
        <h2>Select Operation</h2>
        <form id="pdfForm" action="upload.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="operation">What would you like to do?</label>
                <select id="operation" name="operation">
                    <option value="">-- Select an operation --</option>
                    <option value="merge">Merge PDFs</option>
                    <option value="split">Split PDF into separate pages</option>
                    <option value="compress">Compress PDF</option>
                    <option value="convert">Convert page to image</option>
                    <option value="pdf-to-word">Convert PDF to Word</option>
                </select>
                
                <div id="mergeDetails" class="operation-details">
                    <p><i class="fas fa-info-circle"></i> Combine multiple PDF files into a single document.</p>
                </div>
                
                <div id="splitDetails" class="operation-details">
                    <p><i class="fas fa-info-circle"></i> Extract pages of your PDF into separate PDF files.</p>
                    <label for="startPage">Start Page:</label>
                    <input type="number" id="startPage" name="startPage" min="1" value="1">
                    <label for="endPage">End Page:</label>
                    <input type="number" id="endPage" name="endPage" min="1" value="1">
                </div>
                
                <div id="compressDetails" class="operation-details">
                    <p><i class="fas fa-info-circle"></i> Reduce the file size of your PDF document.</p>
                    <p><small>Requires GhostScript installation on the server.</small></p>
                </div>
                
                <div id="convertDetails" class="operation-details">
                    <p><i class="fas fa-info-circle"></i> Convert a PDF page to a PNG image.</p>
                    <label for="convertPage">Page Number:</label>
                    <input type="number" id="convertPage" name="convertPage" min="1" value="1">
                    <p><small>Requires ImageMagick installation on the server.</small></p>
                </div>

                
            
                <div id="compressDetails" class="operation-details">
                    <p><i class="fas fa-info-circle"></i> Reduce the file size of your PDF document.</p>
                    <p><small>Requires GhostScript installation on the server.</small></p>
                </div>

                <div id="pdfToWordDetails" class="operation-details">
                    <p><i class="fas fa-info-circle"></i> Convert your PDF document to an editable Word document (DOCX format).</p>
                    <p><small>Requires LibreOffice or OpenOffice installation on the server.</small></p>
                </div>
            </div>
            
            <div class="form-group" id="singleFileGroup">
                <label>Upload PDF file</label>
                <div class="file-input-wrapper">
                    <div class="file-input-button" id="dropZone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop your PDF here</p>
                        <p>or</p>
                        <p><strong>Click to browse</strong></p>
                        <input type="file" id="pdfFile" name="pdf" accept=".pdf" class="file-input">
                    </div>
                    <div id="fileName" class="file-name"></div>
                    <div id="fileError" class="error">Please select a valid PDF file</div>
                </div>
            </div>
            
            <div class="form-group" id="multiFileGroup" style="display: none;">
                <label>Upload PDF files to merge (Select 2 files)</label>
                <div class="file-input-wrapper">
                    <div class="file-input-button" id="multiDropZone">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Drag & drop your PDFs here</p>
                        <p>or</p>
                        <p><strong>Click to browse</strong></p>
                        <input type="file" id="pdfFiles" name="pdfs[]" accept=".pdf" class="file-input" multiple>
                    </div>
                    <div id="fileNames" class="file-name"></div>
                    <div id="multiFileError" class="error">Please select at least 2 valid PDF files</div>
                </div>
            </div>
            
            <button type="submit" id="submitBtn" disabled>
                <i class="fas fa-cog"></i> Process PDF
            </button>
        </form>
        
        <div id="loading" class="loading">
            <div class="spinner"></div>
            <p>Processing your PDF. Please wait...</p>
        </div>
    </div>
    
    <div class="info-box">
        <h3><i class="fas fa-lightbulb"></i> Tips for best results</h3>
        <ul>
            <li>Some PDFs with advanced compression techniques may not be compatible with the free FPDI parser.</li>
            <li>If you encounter errors with splitting or merging, try using the compression option first.</li>
            <li>For best image conversion quality, ensure your PDF has high-resolution elements.</li>
        </ul>
    </div>
    
    <div class="features">
        <div class="feature">
            <i class="fas fa-object-group"></i>
            <h3>Merge</h3>
            <p>Combine multiple PDFs into a single document</p>
        </div>
        <div class="feature">
            <i class="fas fa-cut"></i>
            <h3>Split</h3>
            <p>Extract pages into separate PDFs</p>
        </div>
        <div class="feature">
            <i class="fas fa-compress-alt"></i>
            <h3>Compress</h3>
            <p>Reduce file size while preserving quality</p>
        </div>
        <div class="feature">
            <i class="fas fa-image"></i>
            <h3>Convert to Image</h3>
            <p>Transform PDF pages into images</p>
        </div>
        <div class="feature">
            <i class="fas fa-file-word"></i>
            <h3>Convert to Word</h3>
            <p>Transform PDFs into editable Word documents</p>
        </div>
    </div>
    
    <footer>
        <p>PDF Toolkit © <?php echo date('Y'); ?>. All operations are performed locally on the server.</p>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const operationSelect = document.getElementById('operation');
            const mergeDetails = document.getElementById('mergeDetails');
            const splitDetails = document.getElementById('splitDetails');
            const compressDetails = document.getElementById('compressDetails');
            const convertDetails = document.getElementById('convertDetails');
            const fileInput = document.getElementById('pdfFile');
            const filesInput = document.getElementById('pdfFiles');
            const fileName = document.getElementById('fileName');
            const fileNames = document.getElementById('fileNames');
            const fileError = document.getElementById('fileError');
            const multiFileError = document.getElementById('multiFileError');
            const submitBtn = document.getElementById('submitBtn');
            const pdfForm = document.getElementById('pdfForm');
            const loading = document.getElementById('loading');
            const dropZone = document.getElementById('dropZone');
            const multiDropZone = document.getElementById('multiDropZone');
            const singleFileGroup = document.getElementById('singleFileGroup');
            const multiFileGroup = document.getElementById('multiFileGroup');
            
            // Update the operationSelect.addEventListener('change', ...) function:
            operationSelect.addEventListener('change', function() {
                // Hide all details first
                mergeDetails.style.display = 'none';
                splitDetails.style.display = 'none';
                compressDetails.style.display = 'none';
                convertDetails.style.display = 'none';
                pdfToWordDetails.style.display = 'none';
                
                // Show selected operation details
                switch(this.value) {
                    case 'merge':
                        mergeDetails.style.display = 'block';
                        singleFileGroup.style.display = 'none';
                        multiFileGroup.style.display = 'block';
                        break;
                    case 'split':
                        singleFileGroup.style.display = 'block';
                        multiFileGroup.style.display = 'none';
                        splitDetails.style.display = 'block';
                        break;
                    case 'compress':
                        singleFileGroup.style.display = 'block';
                        multiFileGroup.style.display = 'none';
                        compressDetails.style.display = 'block';
                        break;
                    case 'convert':
                        singleFileGroup.style.display = 'block';
                        multiFileGroup.style.display = 'none';
                        convertDetails.style.display = 'block';
                        break;
                    case 'pdf-to-word':
                        singleFileGroup.style.display = 'block';
                        multiFileGroup.style.display = 'none';
                        pdfToWordDetails.style.display = 'block';
                        break;
                    default:
                        singleFileGroup.style.display = 'block';
                        multiFileGroup.style.display = 'none';
                }
                
                validateForm();
            });
            
            // File input handling for single file
            fileInput.addEventListener('change', function() {
                handleFileInput(this, fileName, fileError, dropZone);
                validateForm();
            });
            
            // File input handling for multiple files
            filesInput.addEventListener('change', function() {
                handleMultiFileInput(this, fileNames, multiFileError, multiDropZone);
                validateForm();
            });
            
            function handleFileInput(input, nameDisplay, errorDisplay, zone) {
                if(input.files.length > 0) {
                    const file = input.files[0];
                    if(file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                        nameDisplay.textContent = file.name;
                        nameDisplay.style.display = 'block';
                        errorDisplay.style.display = 'none';
                        zone.style.borderColor = '#4cc9f0';
                    } else {
                        nameDisplay.style.display = 'none';
                        errorDisplay.style.display = 'block';
                        zone.style.borderColor = '#ef476f';
                    }
                } else {
                    nameDisplay.style.display = 'none';
                    errorDisplay.style.display = 'none';
                    zone.style.borderColor = '#dee2e6';
                }
            }
            
            function handleMultiFileInput(input, nameDisplay, errorDisplay, zone) {
                if(input.files.length > 0) {
                    let allValid = true;
                    let names = [];
                    
                    for(let i = 0; i < input.files.length; i++) {
                        const file = input.files[i];
                        if(!(file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'))) {
                            allValid = false;
                            break;
                        }
                        names.push(file.name);
                    }
                    
                    if(allValid && input.files.length >= 2) {
                        nameDisplay.textContent = names.join(', ');
                        nameDisplay.style.display = 'block';
                        errorDisplay.style.display = 'none';
                        zone.style.borderColor = '#4cc9f0';
                    } else {
                        nameDisplay.style.display = 'none';
                        errorDisplay.style.display = 'block';
                        zone.style.borderColor = '#ef476f';
                    }
                } else {
                    nameDisplay.style.display = 'none';
                    errorDisplay.style.display = 'none';
                    zone.style.borderColor = '#dee2e6';
                }
            }
            
            // Drag and drop functionality for single file
            setupDropZone(dropZone, fileInput);
            
            // Drag and drop functionality for multiple files
            setupDropZone(multiDropZone, filesInput);
            
            function setupDropZone(zone, input) {
                zone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.style.backgroundColor = '#e8f0fe';
                    this.style.borderColor = '#4361ee';
                });
                
                zone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.style.backgroundColor = '#f8f9fa';
                    this.style.borderColor = '#dee2e6';
                });
                
                zone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.style.backgroundColor = '#f8f9fa';
                    
                    if(e.dataTransfer.files.length > 0) {
                        input.files = e.dataTransfer.files;
                        
                        // Trigger change event manually
                        const event = new Event('change');
                        input.dispatchEvent(event);
                    }
                });
            }
            
            // Form validation
            function validateForm() {
                const operation = operationSelect.value;
                let isValid = false;
                
                if(operation === 'merge') {
                    if(filesInput.files.length >= 2) {
                        let allValid = true;
                        for(let i = 0; i < filesInput.files.length; i++) {
                            const file = filesInput.files[i];
                            if(!(file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'))) {
                                allValid = false;
                                break;
                            }
                        }
                        isValid = allValid;
                    }
                } else if(operation === 'split') {
                    const startPage = document.getElementById('startPage').value;
                    const endPage = document.getElementById('endPage').value;
                    if(fileInput.files.length > 0 && startPage && endPage && startPage <= endPage) {
                        const file = fileInput.files[0];
                        isValid = (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'));
                    }
                } else if(operation) {
                    if(fileInput.files.length > 0) {
                        const file = fileInput.files[0];
                        isValid = (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'));
                    }
                }
                
                submitBtn.disabled = !isValid;
            }
            
            // Show loading animation on form submit
            pdfForm.addEventListener('submit', function() {
                if(!submitBtn.disabled) {
                    loading.style.display = 'block';
                    submitBtn.disabled = true;
                }
            });
        });
    </script>
</body>
</html>