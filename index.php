<?php
// Start session to store uploaded files temporarily
session_start();
require_once 'auth.php';
Auth::redirectIfNotLoggedIn();

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
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> | 
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
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
        const pdfToWordDetails = document.getElementById('pdfToWordDetails');
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
        
        // Add animation to the header elements
        setTimeout(() => {
            document.querySelector('header h1').classList.add('fade-in');
            setTimeout(() => {
                document.querySelector('header .subtitle').classList.add('fade-in');
                setTimeout(() => {
                    document.querySelector('header .user-info').classList.add('fade-in');
                }, 200);
            }, 200);
        }, 100);
        
        // Initialize tooltips
        initializeTooltips();
        
        // Operation selection effect
        operationSelect.addEventListener('change', function() {
            // Hide all details first
            const allDetails = document.querySelectorAll('.operation-details');
            allDetails.forEach(detail => {
                detail.style.display = 'none';
            });
            
            // Remove active class from features
            document.querySelectorAll('.feature').forEach(feature => {
                feature.classList.remove('active');
            });
            
            // Reset file inputs
            fileInput.value = '';
            filesInput.value = '';
            fileName.style.display = 'none';
            fileNames.style.display = 'none';
            
            // Show selected operation details with animation
            switch(this.value) {
                case 'merge':
                    fadeIn(mergeDetails);
                    singleFileGroup.style.display = 'none';
                    multiFileGroup.style.display = 'block';
                    highlightFeature('Merge');
                    break;
                case 'split':
                    fadeIn(splitDetails);
                    singleFileGroup.style.display = 'block';
                    multiFileGroup.style.display = 'none';
                    highlightFeature('Split');
                    break;
                case 'compress':
                    fadeIn(compressDetails);
                    singleFileGroup.style.display = 'block';
                    multiFileGroup.style.display = 'none';
                    highlightFeature('Compress');
                    break;
                case 'convert':
                    fadeIn(convertDetails);
                    singleFileGroup.style.display = 'block';
                    multiFileGroup.style.display = 'none';
                    highlightFeature('Convert to Image');
                    break;
                case 'pdf-to-word':
                    fadeIn(pdfToWordDetails);
                    singleFileGroup.style.display = 'block';
                    multiFileGroup.style.display = 'none';
                    highlightFeature('Convert to Word');
                    break;
                default:
                    singleFileGroup.style.display = 'block';
                    multiFileGroup.style.display = 'none';
            }
            
            validateForm();
        });
        
        function highlightFeature(title) {
            document.querySelectorAll('.feature h3').forEach(heading => {
                if (heading.textContent === title) {
                    heading.parentElement.classList.add('active');
                }
            });
        }
        
        function fadeIn(element) {
            element.style.display = 'block';
            element.style.opacity = '0';
            setTimeout(() => {
                element.style.opacity = '1';
            }, 10);
        }
        
        // File input handling for single file with visual feedback
        fileInput.addEventListener('change', function() {
            handleFileInput(this, fileName, fileError, dropZone);
            validateForm();
        });
        
        // File input handling for multiple files with visual feedback
        filesInput.addEventListener('change', function() {
            handleMultiFileInput(this, fileNames, multiFileError, multiDropZone);
            validateForm();
        });
        
        function handleFileInput(input, nameDisplay, errorDisplay, zone) {
            zone.classList.remove('success', 'error');
            
            if(input.files.length > 0) {
                const file = input.files[0];
                if(file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                    nameDisplay.textContent = file.name;
                    nameDisplay.style.display = 'block';
                    errorDisplay.style.display = 'none';
                    zone.classList.add('success');
                    
                    // Show file size info
                    const size = (file.size / 1024 / 1024).toFixed(2);
                    nameDisplay.innerHTML = `<i class="fas fa-file-pdf"></i> <strong>${file.name}</strong> (${size} MB)`;
                } else {
                    nameDisplay.style.display = 'none';
                    errorDisplay.style.display = 'block';
                    zone.classList.add('error');
                }
            } else {
                nameDisplay.style.display = 'none';
                errorDisplay.style.display = 'none';
                zone.classList.remove('success', 'error');
            }
        }
        
        function handleMultiFileInput(input, nameDisplay, errorDisplay, zone) {
            zone.classList.remove('success', 'error');
            
            if(input.files.length > 0) {
                let allValid = true;
                let names = [];
                let totalSize = 0;
                
                for(let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    if(!(file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'))) {
                        allValid = false;
                        break;
                    }
                    names.push(file.name);
                    totalSize += file.size;
                }
                
                if(allValid && input.files.length >= 2) {
                    nameDisplay.innerHTML = `<i class="fas fa-check-circle"></i> <strong>${input.files.length} PDFs selected</strong> (${(totalSize / 1024 / 1024).toFixed(2)} MB total)`;
                    nameDisplay.style.display = 'block';
                    errorDisplay.style.display = 'none';
                    zone.classList.add('success');
                } else {
                    nameDisplay.style.display = 'none';
                    errorDisplay.style.display = 'block';
                    zone.classList.add('error');
                }
            } else {
                nameDisplay.style.display = 'none';
                errorDisplay.style.display = 'none';
                zone.classList.remove('success', 'error');
            }
        }
        
        // Enhanced drag and drop functionality
        setupEnhancedDropZone(dropZone, fileInput);
        setupEnhancedDropZone(multiDropZone, filesInput);
        
        function setupEnhancedDropZone(zone, input) {
            // Hover effects
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            zone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });
            
            // Drop effect with animation
            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                if(e.dataTransfer.files.length > 0) {
                    // Visual feedback
                    this.classList.add('dropped');
                    setTimeout(() => {
                        this.classList.remove('dropped');
                    }, 600);
                    
                    input.files = e.dataTransfer.files;
                    
                    // Trigger change event manually
                    const event = new Event('change');
                    input.dispatchEvent(event);
                }
            });
            
            // Click effect
            zone.addEventListener('click', function() {
                this.classList.add('clicked');
                setTimeout(() => {
                    this.classList.remove('clicked');
                }, 300);
            });
        }
        
        // Enhanced form validation with visual feedback
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
                if(fileInput.files.length > 0 && startPage && endPage && parseInt(startPage) <= parseInt(endPage)) {
                    const file = fileInput.files[0];
                    isValid = (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'));
                }
            } else if(operation) {
                if(fileInput.files.length > 0) {
                    const file = fileInput.files[0];
                    isValid = (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf'));
                }
            }
            
            if(isValid) {
                submitBtn.disabled = false;
                submitBtn.classList.add('ready');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.remove('ready');
            }
        }
        
        // Validate page numbers for split operation
        const startPage = document.getElementById('startPage');
        const endPage = document.getElementById('endPage');
        
        [startPage, endPage].forEach(input => {
            input.addEventListener('input', function() {
                validateForm();
                validatePageNumbers();
            });
        });
        
        function validatePageNumbers() {
            if(parseInt(startPage.value) > parseInt(endPage.value)) {
                endPage.setCustomValidity('End page must be greater than or equal to start page');
                endPage.reportValidity();
            } else {
                endPage.setCustomValidity('');
            }
        }
        
        // Enhanced loading animation on form submit
        pdfForm.addEventListener('submit', function(e) {
            if(!submitBtn.disabled) {
                loading.style.display = 'flex';
                submitBtn.disabled = true;
                
                // Add animation to loading spinner
                document.querySelector('.spinner').classList.add('active');
                
                // Simulate progress for better UX (in a real app, this would be based on actual progress)
                startProgressSimulation();
            }
        });
        
        function startProgressSimulation() {
            const loadingText = document.querySelector('.loading p');
            const originalText = loadingText.textContent;
            const processingSteps = [
                'Analyzing PDF structure...',
                'Processing pages...',
                'Optimizing output...',
                'Almost done...'
            ];
            
            processingSteps.forEach((text, index) => {
                setTimeout(() => {
                    loadingText.textContent = text;
                }, (index + 1) * 1800);
            });
        }
        
        // Add tooltips for better UX
        function initializeTooltips() {
            const tooltips = [
                { selector: '#operation', text: 'Choose what you want to do with your PDF' },
                { selector: '#startPage', text: 'First page to extract' },
                { selector: '#endPage', text: 'Last page to extract' },
                { selector: '#convertPage', text: 'Page to convert to image' }
            ];
            
            tooltips.forEach(tooltip => {
                const element = document.querySelector(tooltip.selector);
                if(element) {
                    element.setAttribute('title', tooltip.text);
                }
            });
        }
        
        // Add interactive feature highlighting
        document.querySelectorAll('.feature').forEach(feature => {
            feature.addEventListener('click', function() {
                const operationName = this.querySelector('h3').textContent;
                
                // Map feature name to operation value
                const operations = {
                    'Merge': 'merge',
                    'Split': 'split',
                    'Compress': 'compress',
                    'Convert to Image': 'convert',
                    'Convert to Word': 'pdf-to-word'
                };
                
                if(operations[operationName]) {
                    operationSelect.value = operations[operationName];
                    // Trigger change event
                    operationSelect.dispatchEvent(new Event('change'));
                    
                    // Scroll to form
                    document.querySelector('.card').scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    });

    // Add these CSS classes for the animations
    document.head.insertAdjacentHTML('beforeend', `
    <style>
        .fade-in {
            animation: fadeInAnimation 0.8s ease forwards;
            opacity: 0;
        }
        
        @keyframes fadeInAnimation {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .dragover {
            border-color: var(--primary) !important;
            background-color: rgba(67, 97, 238, 0.08) !important;
            transform: scale(1.02);
        }
        
        .file-input-button.success {
            border-color: #28a745;
            background-color: rgba(40, 167, 69, 0.08);
        }
        
        .file-input-button.error {
            border-color: var(--error);
            background-color: rgba(239, 71, 111, 0.08);
        }
        
        .file-input-button.dropped {
            animation: pulse 0.6s ease;
        }
        
        .file-input-button.clicked {
            transform: scale(0.98);
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.03); }
            100% { transform: scale(1); }
        }
        
        button.ready {
            animation: readyPulse 2s infinite;
        }
        
        @keyframes readyPulse {
            0% { box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2); }
            50% { box-shadow: 0 4px 20px rgba(67, 97, 238, 0.4); }
            100% { box-shadow: 0 4px 12px rgba(67, 97, 238, 0.2); }
        }
        
        .spinner.active {
            animation: spin 1s linear infinite, grow 1.5s ease-in-out infinite alternate;
        }
        
        @keyframes grow {
            from { transform: rotate(0deg) scale(0.95); }
            to { transform: rotate(360deg) scale(1.05); }
        }
        
        .feature.active {
            transform: translateY(-8px);
            box-shadow: 0 12px 20px rgba(67, 97, 238, 0.15);
        }
        
        .feature.active::before {
            transform: scaleX(1);
        }
        
        .feature.active i {
            color: white;
            background: var(--primary);
        }
    </style>
    `);
    </script>
</body>
</html>