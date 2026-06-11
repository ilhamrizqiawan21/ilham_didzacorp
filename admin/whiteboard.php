<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Papan Tulis Digital';
include '../includes/header.php';
?>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-chalkboard"></i> Papan Tulis Digital</h2>
    <p class="page-subtitle">Tulis, gambar, dan jelaskan materi secara interaktif. Cocok untuk proyektor.</p>
</div>

<style>
    /* Toolbar styling */
    .whiteboard-toolbar {
        background: white;
        border-radius: 1rem;
        padding: 1rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        display: flex;
        flex-wrap: wrap;
        gap: 0.8rem;
        align-items: center;
        justify-content: center;
    }
    .tool-group {
        display: flex;
        gap: 0.5rem;
        background: #f1f5f9;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        align-items: center;
    }
    .tool-group label {
        font-size: 0.8rem;
        font-weight: 500;
    }
    .tool-btn {
        background: white;
        border: 1px solid #cbd5e1;
        border-radius: 2rem;
        padding: 0.5rem 1rem;
        cursor: pointer;
        font-size: 0.8rem;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
    .tool-btn:hover {
        background: #e2e8f0;
        transform: translateY(-1px);
    }
    .tool-btn.active {
        background: #10b981;
        border-color: #10b981;
        color: white;
    }
    .color-preview {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        border: 2px solid white;
        box-shadow: 0 0 0 1px #ccc;
        cursor: pointer;
    }
    input[type="color"] {
        width: 40px;
        height: 40px;
        border: none;
        background: transparent;
        cursor: pointer;
    }
    .canvas-container {
        background: #fef9e6;
        border-radius: 1rem;
        padding: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        position: relative;
    }
    canvas {
        display: block;
        width: 100%;
        height: auto;
        background: white;
        border-radius: 0.5rem;
        cursor: crosshair;
        touch-action: none; /* Untuk touchscreen */
    }
    .size-slider {
        width: 120px;
    }
    @media (max-width: 768px) {
        .tool-group { padding: 0.3rem 0.8rem; }
        .tool-btn { padding: 0.3rem 0.8rem; font-size: 0.7rem; }
    }
</style>

<div class="whiteboard-toolbar">
    <div class="tool-group">
        <button id="drawBtn" class="tool-btn active"><i class="fas fa-pencil-alt"></i> Gambar</button>
        <button id="textBtn" class="tool-btn"><i class="fas fa-font"></i> Teks</button>
        <button id="lineBtn" class="tool-btn"><i class="fas fa-slash"></i> Garis</button>
        <button id="rectBtn" class="tool-btn"><i class="fas fa-square"></i> Kotak</button>
        <button id="circleBtn" class="tool-btn"><i class="fas fa-circle"></i> Lingkaran</button>
        <button id="eraserBtn" class="tool-btn"><i class="fas fa-eraser"></i> Penghapus</button>
    </div>
    <div class="tool-group">
        <label>Warna:</label>
        <input type="color" id="colorPicker" value="#000000">
        <div class="color-preview" style="background:#ff0000;" data-color="#ff0000"></div>
        <div class="color-preview" style="background:#0000ff;" data-color="#0000ff"></div>
        <div class="color-preview" style="background:#10b981;" data-color="#10b981"></div>
        <div class="color-preview" style="background:#f59e0b;" data-color="#f59e0b"></div>
    </div>
    <div class="tool-group">
        <label>Ukuran:</label>
        <input type="range" id="brushSize" min="1" max="30" value="3" class="size-slider">
        <span id="sizeValue">3</span> px
    </div>
    <div class="tool-group">
        <button id="clearBtn" class="tool-btn"><i class="fas fa-trash-alt"></i> Hapus Semua</button>
        <button id="saveBtn" class="tool-btn"><i class="fas fa-camera"></i> Simpan Gambar</button>
        <button id="fullscreenBtn" class="tool-btn"><i class="fas fa-expand"></i> Layar Penuh</button>
    </div>
</div>

<div class="canvas-container">
    <canvas id="whiteboardCanvas" width="1000" height="600" style="width:100%; height:auto; max-width:100%; aspect-ratio:1000/600"></canvas>
</div>

<script>
    (function() {
        const canvas = document.getElementById('whiteboardCanvas');
        const ctx = canvas.getContext('2d');
        
        // Set dimensi canvas sesuai rasio
        function resizeCanvas() {
            const container = canvas.parentElement;
            const width = container.clientWidth - 20;
            canvas.width = 1000;
            canvas.height = 600;
            // restore gambar jika ada (simpan sementara)
            if (savedImageData) {
                const img = new Image();
                img.onload = () => ctx.drawImage(img, 0, 0);
                img.src = savedImageData;
            } else {
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            }
        }
        
        let painting = false;
        let currentTool = 'draw'; // draw, text, line, rect, circle, eraser
        let currentColor = '#000000';
        let brushSize = 3;
        let startX, startY;
        let savedImageData = null;
        
        // Update UI
        const drawBtn = document.getElementById('drawBtn');
        const textBtn = document.getElementById('textBtn');
        const lineBtn = document.getElementById('lineBtn');
        const rectBtn = document.getElementById('rectBtn');
        const circleBtn = document.getElementById('circleBtn');
        const eraserBtn = document.getElementById('eraserBtn');
        const colorPicker = document.getElementById('colorPicker');
        const brushSizeSlider = document.getElementById('brushSize');
        const sizeSpan = document.getElementById('sizeValue');
        const clearBtn = document.getElementById('clearBtn');
        const saveBtn = document.getElementById('saveBtn');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        
        function setActiveTool(active) {
            [drawBtn, textBtn, lineBtn, rectBtn, circleBtn, eraserBtn].forEach(btn => btn.classList.remove('active'));
            if (active === 'draw') drawBtn.classList.add('active');
            else if (active === 'text') textBtn.classList.add('active');
            else if (active === 'line') lineBtn.classList.add('active');
            else if (active === 'rect') rectBtn.classList.add('active');
            else if (active === 'circle') circleBtn.classList.add('active');
            else if (active === 'eraser') eraserBtn.classList.add('active');
        }
        
        drawBtn.addEventListener('click', () => { currentTool = 'draw'; setActiveTool('draw'); });
        textBtn.addEventListener('click', () => { currentTool = 'text'; setActiveTool('text'); });
        lineBtn.addEventListener('click', () => { currentTool = 'line'; setActiveTool('line'); });
        rectBtn.addEventListener('click', () => { currentTool = 'rect'; setActiveTool('rect'); });
        circleBtn.addEventListener('click', () => { currentTool = 'circle'; setActiveTool('circle'); });
        eraserBtn.addEventListener('click', () => { currentTool = 'eraser'; setActiveTool('eraser'); });
        
        colorPicker.addEventListener('input', (e) => { currentColor = e.target.value; });
        brushSizeSlider.addEventListener('input', (e) => { brushSize = parseInt(e.target.value); sizeSpan.innerText = brushSize; });
        
        // Warna cepat
        document.querySelectorAll('.color-preview').forEach(el => {
            el.addEventListener('click', () => {
                currentColor = el.dataset.color;
                colorPicker.value = currentColor;
            });
        });
        
        // Fungsi menggambar
        function startDrawing(e) {
            painting = true;
            const pos = getCanvasCoords(e);
            startX = pos.x;
            startY = pos.y;
            if (currentTool === 'draw') {
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
            if (!painting) return;
            const pos = getCanvasCoords(e);
            const currentX = pos.x;
            const currentY = pos.y;
            
            if (currentTool === 'draw') {
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
            } else if (currentTool === 'line' && painting) {
                // Preview sementara saat drag, tapi kita simpan di akhir
                // Untuk sementara kita tidak menggambar langsung, tunggu stopDrawing
            } else if (currentTool === 'rect' || currentTool === 'circle') {
                // preview di stopDrawing
            }
        }
        
        function stopDrawing(e) {
            if (!painting) return;
            painting = false;
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
            } else if (currentTool === 'text') {
                const text = prompt('Masukkan teks:', '');
                if (text) {
                    ctx.font = `${brushSize * 4}px "Segoe UI", system-ui`;
                    ctx.fillStyle = currentColor;
                    ctx.fillText(text, endX, endY);
                }
            }
            // Simpan state untuk resize
            savedImageData = canvas.toDataURL();
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
        
        // Event mouse & touch
        canvas.addEventListener('mousedown', startDrawing);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDrawing);
        canvas.addEventListener('touchstart', startDrawing);
        canvas.addEventListener('touchmove', draw);
        canvas.addEventListener('touchend', stopDrawing);
        
        clearBtn.addEventListener('click', () => {
            if (confirm('Hapus semua gambar?')) {
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                savedImageData = canvas.toDataURL();
            }
        });
        
        saveBtn.addEventListener('click', () => {
            const link = document.createElement('a');
            link.download = 'papan_tulis.png';
            link.href = canvas.toDataURL();
            link.click();
        });
        
        fullscreenBtn.addEventListener('click', () => {
            const container = document.querySelector('.canvas-container');
            if (container.requestFullscreen) container.requestFullscreen();
            else if (container.webkitRequestFullscreen) container.webkitRequestFullscreen();
        });
        
        // Inisialisasi canvas putih
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        savedImageData = canvas.toDataURL();
        
        // Resize handler
        window.addEventListener('resize', () => {
            const tempData = canvas.toDataURL();
            setTimeout(() => {
                const img = new Image();
                img.onload = () => {
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    savedImageData = canvas.toDataURL();
                };
                img.src = tempData;
            }, 50);
        });
    })();
</script>

<?php include '../includes/footer.php'; ?>