<?php
// Set page title
$pageTitle = "PDF Coordinate Viewer";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <script>
        // Set the PDF.js worker source
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';
    </script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
        }
        .upload-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 2px dashed #ccc;
            border-radius: 5px;
            text-align: center;
        }
        .viewer-section {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        #pdf-container {
            position: relative;
            border: 1px solid #ddd;
            margin: 20px 0;
            overflow: auto;
            max-width: 100%;
            background-color: #525659;
            cursor: crosshair;
        }
        #pdf-canvas {
            display: block;
            margin: 0 auto;
        }
        #coordinates {
            position: fixed;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 12px 16px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 16px;
            z-index: 1000;
        }
        .controls {
            margin: 15px 0;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .overlay-grid {
            position: absolute;
            top: 0;
            left: 0;
            pointer-events: none;
            z-index: 10;
        }
        #measurement-canvas {
            position: absolute;
            top: 0;
            left: 0;
            z-index: 15;
        }
        .page-nav {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 10px;
            gap: 10px;
        }
        #page-num, #page-count {
            font-weight: bold;
        }
        .grid-toggle {
            margin-top: 10px;
        }
        .measurement-info {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 12px 16px;
            border-radius: 4px;
            font-size: 16px;
            z-index: 1000;
        }
        .tool-panel {
            margin: 15px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: #f9f9f9;
        }
        .tool-panel label {
            margin-right: 15px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo $pageTitle; ?></h1>
        
        <div class="upload-section">
            <h3>Select a PDF file</h3>
            <input type="file" id="pdf-file" accept="application/pdf">
            <p>Or select from available templates:</p>
            <select id="template-select">
                <option value="">-- Select a template --</option>
                <option value="assets/pdf/A4.pdf">A4 Template</option>
                <option value="assets/pdf/A6.pdf">A6 Template</option>
                <option value="assets/pdf/CARD.pdf">Card Template</option>
                <option value="assets/pdf/lab_grown/A4.pdf">Lab Grown A4</option>
                <option value="assets/pdf/lab_grown/CARD.pdf">Lab Grown Card</option>
                <option value="assets/pdf/colored_stone/A4.pdf">Colored Stone A4</option>
                <option value="assets/pdf/diamond_sealing/A4.pdf">Diamond Sealing A4</option>
            </select>
        </div>
        
        <div class="tool-panel">
            <label>
                <input type="radio" name="tool" value="navigate" checked> Navigate
            </label>
            <label>
                <input type="radio" name="tool" value="measure"> Measure Length/Width
            </label>
        </div>
        
        <div id="coordinates">X: 0.0mm, Y: 0.0mm</div>
        <div id="measurement-info" class="measurement-info" style="display:none;">
            Length: 0mm, Width: 0mm
        </div>
        
        <div class="viewer-section">
            <div class="controls">
                <button id="prev-page">Previous Page</button>
                <div class="page-nav">
                    Page <span id="page-num">1</span> of <span id="page-count">1</span>
                </div>
                <button id="next-page">Next Page</button>
            </div>
            
            <div id="pdf-container">
                <canvas id="pdf-canvas"></canvas>
                <canvas id="grid-canvas" class="overlay-grid"></canvas>
                <canvas id="measurement-canvas"></canvas>
            </div>
            
            <div class="grid-toggle">
                <label>
                    <input type="checkbox" id="show-grid" checked>
                    Show Coordinate Grid
                </label>
                <label style="margin-left: 20px;">
                    <input type="checkbox" id="show-major-grid" checked>
                    Show Major Grid Lines
                </label>
            </div>
            
            <div class="instructions" style="margin-top:15px;text-align:center;color:#555;">
                <p><strong>Instructions:</strong> Select "Measure" tool and click-drag to measure length and width</p>
            </div>
        </div>
    </div>

    <script>
        let pdfDoc = null;
        let pageNum = 1;
        let pageRendering = false;
        let pageNumPending = null;
        let scale = 1.5;
        let canvas = document.getElementById('pdf-canvas');
        let gridCanvas = document.getElementById('grid-canvas');
        let measurementCanvas = document.getElementById('measurement-canvas');
        let ctx = canvas.getContext('2d');
        let gridCtx = gridCanvas.getContext('2d');
        let measureCtx = measurementCanvas.getContext('2d');
        let pdfContainer = document.getElementById('pdf-container');
        let coordDisplay = document.getElementById('coordinates');
        let measurementInfo = document.getElementById('measurement-info');
        
        // Measurement variables
        let isDrawing = false;
        let startX = 0;
        let startY = 0;
        let endX = 0;
        let endY = 0;
        let currentTool = 'navigate';
        
        // Tool selection
        document.querySelectorAll('input[name="tool"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                currentTool = this.value;
                
                if (currentTool === 'measure') {
                    measurementInfo.style.display = 'block';
                    pdfContainer.style.cursor = 'crosshair';
                } else {
                    measurementInfo.style.display = 'none';
                    pdfContainer.style.cursor = 'default';
                }
            });
        });
        
        // Load PDF from file input
        document.getElementById('pdf-file').addEventListener('change', function(e) {
            let file = e.target.files[0];
            if (file) {
                let fileReader = new FileReader();
                fileReader.onload = function() {
                    let typedArray = new Uint8Array(this.result);
                    loadPdfFromData(typedArray);
                };
                fileReader.readAsArrayBuffer(file);
            }
        });
        
        // Load PDF from template select
        document.getElementById('template-select').addEventListener('change', function(e) {
            let templatePath = e.target.value;
            if (templatePath) {
                loadPdfFromUrl(templatePath);
            }
        });
        
        // Load PDF from URL
        function loadPdfFromUrl(url) {
            // Using fetch to get the PDF file
            fetch(url)
                .then(response => response.arrayBuffer())
                .then(arrayBuffer => {
                    loadPdfFromData(new Uint8Array(arrayBuffer));
                })
                .catch(error => {
                    console.error('Error fetching PDF:', error);
                    alert('Error loading PDF: ' + error.message);
                });
        }
        
        // Load PDF from array buffer data
        function loadPdfFromData(data) {
            pdfjsLib.getDocument({data: data}).promise.then(function(pdfDoc_) {
                pdfDoc = pdfDoc_;
                document.getElementById('page-count').textContent = pdfDoc.numPages;
                
                // Reset to first page
                pageNum = 1;
                renderPage(pageNum);
            }).catch(function(error) {
                console.error('Error loading PDF:', error);
                alert('Error loading PDF: ' + error.message);
            });
        }
        
        // Render the specified page
        function renderPage(num) {
            pageRendering = true;
            
            // Get page
            pdfDoc.getPage(num).then(function(page) {
                // Get viewport at current scale
                let viewport = page.getViewport({scale: scale});
                
                // Set canvas dimensions to match the viewport
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                gridCanvas.height = viewport.height;
                gridCanvas.width = viewport.width;
                measurementCanvas.height = viewport.height;
                measurementCanvas.width = viewport.width;
                
                // Render PDF page
                let renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                
                let renderTask = page.render(renderContext);
                
                // After rendering PDF, draw the grid
                renderTask.promise.then(function() {
                    drawGrid();
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        // New page rendering is pending
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
                
                // Update page counters
                document.getElementById('page-num').textContent = num;
            });
        }
        
        // Draw coordinate grid on the overlay canvas
        function drawGrid() {
            if (!document.getElementById('show-grid').checked) {
                gridCtx.clearRect(0, 0, gridCanvas.width, gridCanvas.height);
                return;
            }
            
            gridCtx.clearRect(0, 0, gridCanvas.width, gridCanvas.height);
            
            let showMajorGrid = document.getElementById('show-major-grid').checked;
            let w = gridCanvas.width;
            let h = gridCanvas.height;
            
            // Convert mm to canvas pixels
            // A4 in mm is 210x297, we need to map this to canvas dimensions
            let xFactor = w / 210; // 210mm is A4 width
            let yFactor = h / 297; // 297mm is A4 height
            
            // Draw minor grid lines (every 5mm)
            gridCtx.beginPath();
            gridCtx.strokeStyle = 'rgba(200, 200, 200, 0.5)';
            gridCtx.lineWidth = 0.5;
            
            for (let x = 0; x <= 210; x += 5) {
                let xPos = x * xFactor;
                gridCtx.moveTo(xPos, 0);
                gridCtx.lineTo(xPos, h);
            }
            
            for (let y = 0; y <= 297; y += 5) {
                let yPos = y * yFactor;
                gridCtx.moveTo(0, yPos);
                gridCtx.lineTo(w, yPos);
            }
            
            gridCtx.stroke();
            
            // Draw major grid lines (every 10mm)
            if (showMajorGrid) {
                gridCtx.beginPath();
                gridCtx.strokeStyle = 'rgba(100, 100, 100, 0.7)';
                gridCtx.lineWidth = 1;
                
                for (let x = 0; x <= 210; x += 10) {
                    let xPos = x * xFactor;
                    gridCtx.moveTo(xPos, 0);
                    gridCtx.lineTo(xPos, h);
                }
                
                for (let y = 0; y <= 297; y += 10) {
                    let yPos = y * yFactor;
                    gridCtx.moveTo(0, yPos);
                    gridCtx.lineTo(w, yPos);
                }
                
                gridCtx.stroke();
                
                // Add coordinate labels (larger font size)
                gridCtx.fillStyle = 'red';
                gridCtx.font = '12px Arial';
                
                for (let x = 0; x <= 210; x += 10) {
                    let xPos = x * xFactor;
                    gridCtx.fillText(x.toString(), xPos + 2, 14);
                }
                
                for (let y = 0; y <= 297; y += 10) {
                    let yPos = y * yFactor;
                    gridCtx.fillText(y.toString(), 2, yPos + 14);
                }
            }
        }
        
        // Convert canvas coordinates to PDF mm coordinates
        function canvasToMM(x, y) {
            let mmX = Math.round((x / measurementCanvas.width * 210) * 10) / 10;
            let mmY = Math.round((y / measurementCanvas.height * 297) * 10) / 10;
            return { x: mmX, y: mmY };
        }
        
        // Convert PDF mm coordinates to canvas coordinates
        function mmToCanvas(x, y) {
            let canvasX = x * measurementCanvas.width / 210;
            let canvasY = y * measurementCanvas.height / 297;
            return { x: canvasX, y: canvasY };
        }
        
        // Calculate distance between two points in mm
        function calculateDistance(x1, y1, x2, y2) {
            return Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
        }
        
        // Draw measurement lines
        function drawMeasurement() {
            measureCtx.clearRect(0, 0, measurementCanvas.width, measurementCanvas.height);
            
            if (!isDrawing || currentTool !== 'measure') return;
            
            // Convert to MM coordinates
            let start = canvasToMM(startX, startY);
            let end = canvasToMM(endX, endY);
            
            // Calculate length
            let length = calculateDistance(start.x, start.y, end.x, end.y);
            
            // Calculate width (perpendicular line from the midpoint)
            let midX = (start.x + end.x) / 2;
            let midY = (start.y + end.y) / 2;
            
            // Direction vector of original line
            let dirX = end.x - start.x;
            let dirY = end.y - start.y;
            
            // Perpendicular vector (rotate 90 degrees)
            let perpX = -dirY;
            let perpY = dirX;
            
            // Normalize perpendicular vector and set a fixed width length
            let widthLength = length * 0.3; // You can adjust this width length
            let perpLength = Math.sqrt(perpX * perpX + perpY * perpY);
            let normPerpX = perpX / perpLength * widthLength;
            let normPerpY = perpY / perpLength * widthLength;
            
            // Width line endpoints
            let width1X = midX + normPerpX / 2;
            let width1Y = midY + normPerpY / 2;
            let width2X = midX - normPerpX / 2;
            let width2Y = midY - normPerpY / 2;
            
            // Convert back to canvas coordinates
            let startCanvas = mmToCanvas(start.x, start.y);
            let endCanvas = mmToCanvas(end.x, end.y);
            let width1Canvas = mmToCanvas(width1X, width1Y);
            let width2Canvas = mmToCanvas(width2X, width2Y);
            
            // Draw the length line
            measureCtx.beginPath();
            measureCtx.strokeStyle = 'blue';
            measureCtx.lineWidth = 2;
            measureCtx.moveTo(startCanvas.x, startCanvas.y);
            measureCtx.lineTo(endCanvas.x, endCanvas.y);
            measureCtx.stroke();
            
            // Draw the width line
            measureCtx.beginPath();
            measureCtx.strokeStyle = 'green';
            measureCtx.lineWidth = 2;
            measureCtx.moveTo(width1Canvas.x, width1Canvas.y);
            measureCtx.lineTo(width2Canvas.x, width2Canvas.y);
            measureCtx.stroke();
            
            // Calculate the actual width
            let width = calculateDistance(width1X, width1Y, width2X, width2Y);
            
            // Update measurement info display
            measurementInfo.textContent = `Length: ${length.toFixed(1)}mm, Width: ${width.toFixed(1)}mm`;
        }
        
        // Track mouse movement to show coordinates
        measurementCanvas.addEventListener('mousemove', function(event) {
            let rect = measurementCanvas.getBoundingClientRect();
            let x = event.clientX - rect.left;
            let y = event.clientY - rect.top;
            
            // Convert canvas coordinates to mm (A4 = 210x297mm)
            let mm = canvasToMM(x, y);
            
            coordDisplay.textContent = `X: ${mm.x.toFixed(1)}mm, Y: ${mm.y.toFixed(1)}mm`;
            
            if (isDrawing && currentTool === 'measure') {
                endX = x;
                endY = y;
                drawMeasurement();
            }
        });
        
        // Handle mousedown for measurements
        measurementCanvas.addEventListener('mousedown', function(event) {
            if (currentTool === 'measure') {
                let rect = measurementCanvas.getBoundingClientRect();
                startX = event.clientX - rect.left;
                startY = event.clientY - rect.top;
                endX = startX;
                endY = startY;
                isDrawing = true;
            }
        });
        
        // Handle mouseup for measurements
        measurementCanvas.addEventListener('mouseup', function() {
            isDrawing = false;
        });
        
        // Handle mouseleave to cancel drawing
        measurementCanvas.addEventListener('mouseleave', function() {
            if (isDrawing) {
                isDrawing = false;
            }
        });
        
        // Go to previous page
        document.getElementById('prev-page').addEventListener('click', function() {
            if (pageNum <= 1) return;
            pageNum--;
            queueRenderPage(pageNum);
        });
        
        // Go to next page
        document.getElementById('next-page').addEventListener('click', function() {
            if (pdfDoc === null || pageNum >= pdfDoc.numPages) return;
            pageNum++;
            queueRenderPage(pageNum);
        });
        
        // Queue a new render if needed
        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }
        
        // Toggle grid visibility
        document.getElementById('show-grid').addEventListener('change', drawGrid);
        document.getElementById('show-major-grid').addEventListener('change', drawGrid);
    </script>
</body>
</html>