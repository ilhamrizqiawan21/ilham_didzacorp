<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');

$id = (int)$_GET['id'];
$bab = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM bab WHERE id=$id"));
if (!$bab) {
    die('Bab tidak ditemukan');
}

$file = $bab['file_materi'];
$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
$fileUrl = '../uploads/materi/' . $file;
$fileExists = file_exists($fileUrl);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Preview & Anotasi - <?= htmlspecialchars($bab['judul_bab']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: #1a1a2e;
            display: flex;
            flex-direction: column;
            height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        .annotation-toolbar {
            background: #16213e;
            color: white;
            padding: 8px 16px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            border-bottom: 1px solid #0f3460;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .tool-group {
            display: flex;
            gap: 5px;
            background: #0f3460;
            padding: 4px 10px;
            border-radius: 30px;
            align-items: center;
        }
        .tool-btn {
            background: transparent;
            border: none;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .tool-btn.active {
            background: #10b981;
            color: white;
        }
        .tool-btn:hover:not(.active) {
            background: #2563eb;
        }
        .color-preview {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 2px solid white;
            cursor: pointer;
        }
        input[type="color"] {
            width: 30px;
            height: 30px;
            border: none;
            background: transparent;
            cursor: pointer;
        }
        .size-slider {
            width: 80px;
        }
        .preview-info {
            font-size: 0.8rem;
            background: #0f3460;
            padding: 4px 12px;
            border-radius: 20px;
        }
        .canvas-container {
            flex: 1;
            position: relative;
            background: #2d3748;
            overflow: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #contentCanvas {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
        }
        canvas {
            display: block;
            margin: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            cursor: crosshair;
            transition: transform 0.1s;
        }
        .loading, .error-message {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 1.2rem;
            background: rgba(0,0,0,0.7);
            padding: 12px 24px;
            border-radius: 30px;
            text-align: center;
            z-index: 20;
        }
        .error-message a {
            color: #10b981;
        }
        .page-nav {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .page-nav input {
            width: 50px;
            text-align: center;
            background: #0f3460;
            border: none;
            color: white;
            padding: 4px;
            border-radius: 8px;
        }
        .zoom-group {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        @media (max-width: 768px) {
            .tool-group { padding: 2px 6px; }
            .tool-btn { padding: 4px 8px; font-size: 0.7rem; }
        }
    </style>
</head>
<body>
<div class="annotation-toolbar">
    <div class="tool-group">
        <button id="penBtn" class="tool-btn active"><i class="fas fa-pencil-alt"></i> Pena</button>
        <button id="lineBtn" class="tool-btn"><i class="fas fa-slash"></i> Garis</button>
        <button id="rectBtn" class="tool-btn"><i class="fas fa-square"></i> Kotak</button>
        <button id="circleBtn" class="tool-btn"><i class="fas fa-circle"></i> Lingkaran</button>
        <button id="eraserBtn" class="tool-btn"><i class="fas fa-eraser"></i> Hapus</button>
        <button id="clearBtn" class="tool-btn"><i class="fas fa-trash-alt"></i> Bersih</button>
        <button id="undoBtn" class="tool-btn"><i class="fas fa-undo-alt"></i> Undo</button>
        <button id="saveBtn" class="tool-btn"><i class="fas fa-camera"></i> Simpan</button>
    </div>
    <div class="tool-group">
        <label>Warna:</label>
        <input type="color" id="colorPicker" value="#ff0000">
        <div class="color-preview" style="background:#ff0000;" data-color="#ff0000"></div>
        <div class="color-preview" style="background:#10b981;" data-color="#10b981"></div>
        <div class="color-preview" style="background:#f59e0b;" data-color="#f59e0b"></div>
        <div class="color-preview" style="background:#3b82f6;" data-color="#3b82f6"></div>
    </div>
    <div class="tool-group">
        <label>Ukuran:</label>
        <input type="range" id="brushSize" min="1" max="20" value="3" class="size-slider">
        <span id="sizeValue">3</span>px
    </div>
    <div class="tool-group zoom-group">
        <button id="zoomOutBtn" class="tool-btn"><i class="fas fa-search-minus"></i></button>
        <span id="zoomPercent">100%</span>
        <button id="zoomInBtn" class="tool-btn"><i class="fas fa-search-plus"></i></button>
        <button id="resetZoomBtn" class="tool-btn"><i class="fas fa-expand"></i> Reset</button>
    </div>
    <div id="pdfNavGroup" class="tool-group page-nav" style="display: none;">
        <button id="prevPageBtn" class="tool-btn"><i class="fas fa-chevron-left"></i></button>
        <input type="number" id="pageInput" value="1" min="1">
        <span id="totalPagesSpan"></span>
        <button id="nextPageBtn" class="tool-btn"><i class="fas fa-chevron-right"></i></button>
    </div>
    <div class="preview-info">
        <i class="fas fa-info-circle"></i> <span id="infoText"></span>
    </div>
    <button id="closeBtn" class="tool-btn"><i class="fas fa-times"></i> Tutup</button>
</div>
<div class="canvas-container" id="canvasContainer">
    <div id="contentCanvas"></div>
    <div id="loading" class="loading">Memuat materi...</div>
</div>

<script>
// Inisialisasi variabel
let currentTool = 'pen';
let currentColor = '#ff0000';
let brushSize = 3;
let drawing = false;
let startX, startY;
let canvas, ctx;
let currentPage = 1;
let totalPages = 0;
let pdfDoc = null;
let currentZoom = 1.0;
let history = [];

// Elemen
const contentDiv = document.getElementById('contentCanvas');
const loadingDiv = document.getElementById('loading');
const infoSpan = document.getElementById('infoText');
const pdfNavGroup = document.getElementById('pdfNavGroup');

// Helper: save state ke history
function saveState() {
    if (canvas) {
        const state = canvas.toDataURL();
        history.push(state);
        if (history.length > 30) history.shift();
    }
}

// Undo
function undo() {
    if (history.length > 1) {
        history.pop();
        const prevState = history[history.length - 1];
        const img = new Image();
        img.onload = function() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0);
        };
        img.src = prevState;
    } else {
        alert('Tidak ada aksi sebelumnya');
    }
}

// Bersihkan anotasi
function clearAnnotations() {
    if (confirm('Hapus semua anotasi pada halaman ini?')) {
        renderCurrentPage(true);
        saveState();
    }
}

// Zoom
function applyZoom() {
    if (!canvas) return;
    canvas.style.transform = `scale(${currentZoom})`;
    canvas.style.transformOrigin = 'center center';
    document.getElementById('zoomPercent').innerText = Math.round(currentZoom * 100) + '%';
}
function zoomIn() {
    currentZoom = Math.min(currentZoom + 0.1, 3.0);
    applyZoom();
}
function zoomOut() {
    currentZoom = Math.max(currentZoom - 0.1, 0.5);
    applyZoom();
}
function resetZoom() {
    currentZoom = 1.0;
    applyZoom();
}

// Event drawing
function startDrawing(e) {
    drawing = true;
    const pos = getCanvasCoords(e);
    startX = pos.x;
    startY = pos.y;
    if (currentTool === 'pen') {
        ctx.beginPath();
        ctx.moveTo(startX, startY);
        ctx.lineTo(startX, startY);
        ctx.strokeStyle = currentColor;
        ctx.lineWidth = brushSize;
        ctx.lineCap = 'round';
        ctx.stroke();
    } else if (currentTool === 'eraser') {
        ctx.beginPath();
        ctx.moveTo(startX, startY);
        ctx.lineTo(startX, startY);
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = brushSize;
        ctx.lineCap = 'round';
        ctx.stroke();
    }
}
function draw(e) {
    if (!drawing) return;
    const pos = getCanvasCoords(e);
    const currentX = pos.x;
    const currentY = pos.y;
    if (currentTool === 'pen') {
        ctx.lineTo(currentX, currentY);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(currentX, currentY);
    } else if (currentTool === 'eraser') {
        ctx.lineTo(currentX, currentY);
        ctx.strokeStyle = '#ffffff';
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(currentX, currentY);
    }
}
function stopDrawing(e) {
    if (!drawing) return;
    drawing = false;
    const pos = getCanvasCoords(e);
    const endX = pos.x;
    const endY = pos.y;
    if (currentTool === 'line') {
        ctx.beginPath();
        ctx.moveTo(startX, startY);
        ctx.lineTo(endX, endY);
        ctx.strokeStyle = currentColor;
        ctx.lineWidth = brushSize;
        ctx.stroke();
    } else if (currentTool === 'rect') {
        const width = endX - startX;
        const height = endY - startY;
        ctx.strokeStyle = currentColor;
        ctx.lineWidth = brushSize;
        ctx.strokeRect(startX, startY, width, height);
    } else if (currentTool === 'circle') {
        const radius = Math.hypot(endX - startX, endY - startY);
        ctx.beginPath();
        ctx.arc(startX, startY, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = currentColor;
        ctx.lineWidth = brushSize;
        ctx.stroke();
    }
    saveState();
}
function getCanvasCoords(e) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    let clientX, clientY;
    if (e.touches) {
        clientX = e.touches[0].clientX;
        clientY = e.touches[0].clientY;
        e.preventDefault();
    } else {
        clientX = e.clientX;
        clientY = e.clientY;
    }
    let x = (clientX - rect.left) * scaleX;
    let y = (clientY - rect.top) * scaleY;
    x = Math.min(Math.max(0, x), canvas.width);
    y = Math.min(Math.max(0, y), canvas.height);
    return { x, y };
}

// Render halaman PDF
function renderCurrentPage(keepAnnotations = false) {
    if (!pdfDoc) return;
    loadingDiv.style.display = 'flex';
    pdfDoc.getPage(currentPage).then(function(page) {
        const viewport = page.getViewport({ scale: 1.5 });
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = viewport.width;
        tempCanvas.height = viewport.height;
        const tempCtx = tempCanvas.getContext('2d');
        page.render({ canvasContext: tempCtx, viewport: viewport }).promise.then(function() {
            if (!keepAnnotations || !canvas) {
                if (canvas && canvas.parentNode) canvas.parentNode.removeChild(canvas);
                canvas = document.createElement('canvas');
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                ctx = canvas.getContext('2d');
                ctx.drawImage(tempCanvas, 0, 0);
                contentDiv.innerHTML = '';
                contentDiv.appendChild(canvas);
                attachCanvasEvents();
                history = [];
                saveState();
            }
            infoSpan.innerText = `PDF halaman ${currentPage} dari ${totalPages} | Coret/gambar di atasnya`;
            loadingDiv.style.display = 'none';
            resetZoom();
        });
    }).catch(function(err) {
        loadingDiv.innerHTML = `<div class="error-message">Gagal render halaman: ${err.message}</div>`;
        loadingDiv.style.display = 'flex';
    });
}

// Load gambar (single)
function loadImage(url) {
    const img = new Image();
    img.onload = function() {
        canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);
        contentDiv.innerHTML = '';
        contentDiv.appendChild(canvas);
        attachCanvasEvents();
        history = [];
        saveState();
        loadingDiv.style.display = 'none';
        infoSpan.innerText = 'Gambar siap | Coret/gambar di atasnya';
        resetZoom();
    };
    img.onerror = function() {
        loadingDiv.innerHTML = '<div class="error-message">Gagal memuat gambar. Pastikan file ada. <a href="' + fileUrl + '" download>Download file</a></div>';
        loadingDiv.style.display = 'flex';
    };
    img.src = url;
}

function attachCanvasEvents() {
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('touchstart', startDrawing);
    canvas.addEventListener('touchmove', draw);
    canvas.addEventListener('touchend', stopDrawing);
}

// Navigasi PDF
function goToPage(page) {
    if (!pdfDoc) return;
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    if (page === currentPage) return;
    currentPage = page;
    document.getElementById('pageInput').value = currentPage;
    renderCurrentPage(false);
}

// Event binding
document.addEventListener('DOMContentLoaded', function() {
    // Toolbar
    document.getElementById('penBtn').addEventListener('click', () => {
        currentTool = 'pen';
        document.querySelectorAll('.tool-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('penBtn').classList.add('active');
    });
    document.getElementById('lineBtn').addEventListener('click', () => {
        currentTool = 'line';
        document.querySelectorAll('.tool-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('lineBtn').classList.add('active');
    });
    document.getElementById('rectBtn').addEventListener('click', () => {
        currentTool = 'rect';
        document.querySelectorAll('.tool-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('rectBtn').classList.add('active');
    });
    document.getElementById('circleBtn').addEventListener('click', () => {
        currentTool = 'circle';
        document.querySelectorAll('.tool-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('circleBtn').classList.add('active');
    });
    document.getElementById('eraserBtn').addEventListener('click', () => {
        currentTool = 'eraser';
        document.querySelectorAll('.tool-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('eraserBtn').classList.add('active');
    });
    document.getElementById('clearBtn').addEventListener('click', clearAnnotations);
    document.getElementById('undoBtn').addEventListener('click', undo);
    document.getElementById('saveBtn').addEventListener('click', () => {
        if (canvas) {
            const link = document.createElement('a');
            link.download = `annotasi_${Date.now()}.png`;
            link.href = canvas.toDataURL();
            link.click();
            alert('Gambar anotasi disimpan');
        }
    });
    document.getElementById('closeBtn').addEventListener('click', () => window.close());
    document.getElementById('colorPicker').addEventListener('input', (e) => { currentColor = e.target.value; });
    document.getElementById('brushSize').addEventListener('input', (e) => {
        brushSize = parseInt(e.target.value);
        document.getElementById('sizeValue').innerText = brushSize;
    });
    document.querySelectorAll('.color-preview').forEach(el => {
        el.addEventListener('click', () => {
            currentColor = el.dataset.color;
            document.getElementById('colorPicker').value = currentColor;
        });
    });
    document.getElementById('zoomInBtn').addEventListener('click', zoomIn);
    document.getElementById('zoomOutBtn').addEventListener('click', zoomOut);
    document.getElementById('resetZoomBtn').addEventListener('click', resetZoom);

    // Cek file
    const ext = '<?= $ext ?>';
    const fileUrl = '<?= $fileUrl ?>';
    const fileExists = <?= $fileExists ? 'true' : 'false' ?>;

    if (!fileExists) {
        loadingDiv.innerHTML = '<div class="error-message">File materi tidak ditemukan di server. Silakan upload ulang file.</div>';
        loadingDiv.style.display = 'flex';
        return;
    }

    if (ext === 'pdf') {
        pdfNavGroup.style.display = 'flex';
        pdfjsLib.getDocument(fileUrl).promise.then(function(pdf) {
            pdfDoc = pdf;
            totalPages = pdf.numPages;
            document.getElementById('totalPagesSpan').innerText = `/ ${totalPages}`;
            document.getElementById('pageInput').max = totalPages;
            document.getElementById('pageInput').value = 1;
            renderCurrentPage(false);
        }).catch(function(err) {
            loadingDiv.innerHTML = '<div class="error-message">Gagal memuat PDF: ' + err.message + '</div>';
            loadingDiv.style.display = 'flex';
        });
        document.getElementById('prevPageBtn').addEventListener('click', () => goToPage(currentPage - 1));
        document.getElementById('nextPageBtn').addEventListener('click', () => goToPage(currentPage + 1));
        document.getElementById('pageInput').addEventListener('change', function() {
            let val = parseInt(this.value);
            if (isNaN(val)) val = 1;
            goToPage(val);
        });
    } else if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(ext)) {
        loadImage(fileUrl);
    } else {
        loadingDiv.innerHTML = '<div class="error-message">Format file tidak didukung untuk anotasi langsung. <a href="' + fileUrl + '" download>Download file</a></div>';
        loadingDiv.style.display = 'flex';
    }
});
</script>
</body>
</html>