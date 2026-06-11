<?php
include '../config.php';
include '../includes/fungsi.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
$title = 'Kumpulan Hadits';
include '../includes/header.php';
?>

<style>
/* Font khusus untuk teks Arab */
@import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');

.hadith-container {
    background: #ffffff;
    border-radius: 24px;
    padding: 1.5rem;
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.hadith-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1f2937;
    border-left: 5px solid #10b981;
    padding-left: 1rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.arabic-text {
    font-family: 'Amiri', 'Traditional Arabic', 'Scheherazade New', serif;
    font-size: 1.8rem;
    font-weight: 700;
    line-height: 2.2;
    text-align: right;
    background: #fefce8;
    padding: 1.5rem;
    border-radius: 20px;
    margin-bottom: 1.5rem;
    direction: rtl;
    color: #2c3e2f;
    box-shadow: inset 0 1px 4px rgba(0,0,0,0.02), 0 2px 6px rgba(0,0,0,0.02);
}

.translation-box {
    background: #f8fafc;
    border-radius: 20px;
    padding: 1.2rem 1.5rem;
    border-left: 4px solid #10b981;
}

.translation-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #4b5563;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.translation-text {
    font-size: 1rem;
    line-height: 1.6;
    color: #1e293b;
    text-align: justify;
}

.nav-buttons {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

.nav-buttons .btn {
    min-width: 120px;
    transition: all 0.25s ease;
    border-radius: 40px;
    padding: 0.6rem 1.2rem;
    font-weight: 500;
    background: white;
    border: 1px solid #e2e8f0;
    color: #2d3e50;
}

.nav-buttons .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px -6px rgba(0,0,0,0.1);
    background: #f1f5f9;
    border-color: #cbd5e1;
}

.nav-buttons .btn-primary {
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    color: white;
}

.nav-buttons .btn-primary:hover {
    background: linear-gradient(135deg, #059669, #047857);
    box-shadow: 0 8px 18px -6px rgba(16,185,129,0.4);
}

.search-result-item {
    background: white;
    border-radius: 16px;
    padding: 1rem;
    margin-bottom: 0.8rem;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
    cursor: pointer;
}
.search-result-item:hover {
    background: #fefce8;
    transform: translateX(4px);
    border-color: #d1d5db;
}
.search-result-number {
    font-weight: 700;
    color: #059669;
    margin-bottom: 0.25rem;
}
.search-result-teks {
    font-size: 0.85rem;
    color: #334155;
}
.loading {
    text-align: center;
    padding: 2rem;
    color: #6b7280;
}
.error {
    color: #dc2626;
    background: #fee2e2;
    padding: 1rem;
    border-radius: 16px;
    text-align: center;
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-book"></i> Kumpulan Hadits</h2>
    <p class="page-subtitle">Hadits Rasulullah ﷺ dengan teks Arab dan terjemahan Indonesia</p>
</div>

<!-- Form Pencarian -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-search"></i> Cari Hadits</div>
    <div class="form-row">
        <div class="form-group">
            <label>Pilih Kitab</label>
            <select id="bookSelect" class="form-select">
                <option value="bukhari">Shahih Bukhari</option>
                <option value="muslim">Shahih Muslim</option>
                <option value="abudaud">Sunan Abu Daud</option>
                <option value="tirmidzi">Sunan Tirmidzi</option>
                <option value="nasai">Sunan Nasai</option>
                <option value="ibnumajah">Sunan Ibnu Majah</option>
                <option value="mustadrak">Al-Mustadrak</option>
                <option value="ahmad">Musnad Ahmad</option>
                <option value="syafii">Musnad Syafi'i</option>
                <option value="malik">Muwatha Malik</option>
                <option value="ibnuhibban">Shahih Ibnu Hibban</option>
                <option value="ibnukhuzaimah">Shahih Ibnu Khuzaimah</option>
                <option value="darimi">Sunan Darimi</option>
                <option value="daruquthni">Sunan Daruquthni</option>
            </select>
        </div>
        <div class="form-group">
            <label>Cari Kata Kunci</label>
            <input type="text" id="keywordInput" class="form-input" placeholder="Masukkan kata kunci (Arab/Indonesia)">
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <div>
                <button id="searchBtn" class="btn btn-primary"><i class="fas fa-search"></i> Cari</button>
                <button id="resetSearchBtn" class="btn btn-outline">Reset</button>
            </div>
        </div>
    </div>
    <div class="form-row" style="margin-top: 0.5rem;">
        <div class="form-group">
            <label>Atau langsung ke nomor hadits:</label>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="number" id="numberInput" class="form-input" min="1" value="1" style="width: 120px;">
                <button id="showBtn" class="btn btn-primary">Tampilkan</button>
            </div>
        </div>
    </div>
</div>

<!-- Area Hasil Pencarian -->
<div id="searchResultsContainer" class="form-container" style="display:none;">
    <div class="form-title"><i class="fas fa-list"></i> Hasil Pencarian</div>
    <div id="searchResultsList"></div>
</div>

<!-- Area Detail Hadits -->
<div id="resultContainer" style="display:none;">
    <div class="hadith-container" id="hadithCard"></div>
    <div class="nav-buttons" id="navButtons"></div>
</div>

<script>
const books = {
    bukhari: 'Shahih Bukhari',
    muslim: 'Shahih Muslim',
    abudaud: 'Sunan Abu Daud',
    tirmidzi: 'Sunan Tirmidzi',
    nasai: 'Sunan Nasai',
    ibnumajah: 'Sunan Ibnu Majah',
    mustadrak: 'Al-Mustadrak',
    ahmad: 'Musnad Ahmad',
    syafii: 'Musnad Syafi\'i',
    malik: 'Muwatha Malik',
    ibnuhibban: 'Shahih Ibnu Hibban',
    ibnukhuzaimah: 'Shahih Ibnu Khuzaimah',
    darimi: 'Sunan Darimi',
    daruquthni: 'Sunan Daruquthni'
};

let currentBook = 'bukhari';
let currentNumber = 1;
let totalHadith = 0;

const bookSelect = document.getElementById('bookSelect');
const keywordInput = document.getElementById('keywordInput');
const searchBtn = document.getElementById('searchBtn');
const resetSearchBtn = document.getElementById('resetSearchBtn');
const numberInput = document.getElementById('numberInput');
const showBtn = document.getElementById('showBtn');
const resultContainer = document.getElementById('resultContainer');
const hadithCard = document.getElementById('hadithCard');
const navButtons = document.getElementById('navButtons');
const searchResultsContainer = document.getElementById('searchResultsContainer');
const searchResultsList = document.getElementById('searchResultsList');

async function loadHadith(book, number) {
    searchResultsContainer.style.display = 'none';
    resultContainer.style.display = 'block';
    hadithCard.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-pulse"></i> Memuat hadits...</div>';
    navButtons.innerHTML = '';
    try {
        const response = await fetch(`get_hadith_ajax?book=${book}&number=${number}`);
        const data = await response.json();
        if (data.error) {
            hadithCard.innerHTML = `<div class="error">⚠️ ${data.error}</div>`;
        } else {
            totalHadith = data.total;
            hadithCard.innerHTML = `
                <div class="hadith-title">
                    <i class="fas fa-book-open"></i> ${books[book]} - Hadits No. ${number}
                </div>
                <div class="arabic-text">${(data.arab || '').replace(/\n/g, '<br>')}</div>
                <div class="translation-box">
                    <div class="translation-label"><i class="fas fa-language"></i> Terjemahan</div>
                    <div class="translation-text">${(data.id || '').replace(/\n/g, '<br>')}</div>
                </div>
            `;
            let navHtml = '';
            if (number > 1) navHtml += `<button class="btn" onclick="goTo(${number-1})"><i class="fas fa-chevron-right"></i> Sebelumnya</button>`;
            if (number < totalHadith) navHtml += `<button class="btn btn-primary" onclick="goTo(${number+1})">Selanjutnya <i class="fas fa-chevron-left"></i></button>`;
            navButtons.innerHTML = navHtml;
        }
    } catch (err) {
        hadithCard.innerHTML = `<div class="error">Gagal memuat data: ${err.message}</div>`;
    }
}

function goTo(number) {
    currentNumber = number;
    numberInput.value = number;
    loadHadith(currentBook, currentNumber);
}

async function searchHadith() {
    const book = bookSelect.value;
    const keyword = keywordInput.value.trim();
    if (keyword === '') {
        alert('Masukkan kata kunci pencarian');
        return;
    }
    searchResultsContainer.style.display = 'block';
    searchResultsList.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-pulse"></i> Mencari hadits...</div>';
    resultContainer.style.display = 'none';
    try {
        const response = await fetch(`get_hadith_ajax?book=${book}&action=search&keyword=${encodeURIComponent(keyword)}`);
        if (!response.ok) throw new Error('HTTP error ' + response.status);
        const data = await response.json();
        if (data.error) {
            searchResultsList.innerHTML = `<div class="error">${data.error}</div>`;
        } else if (data.results && data.results.length > 0) {
            let html = `<p><i class="fas fa-check-circle"></i> Ditemukan ${data.total} hadits (maksimal 20):</p>`;
            data.results.forEach(item => {
                html += `
                    <div class="search-result-item" onclick="loadHadith('${book}', ${item.number})">
                        <div class="search-result-number">📖 Hadits No. ${item.number}</div>
                        <div class="search-result-teks">${escapeHtml(item.teks)}</div>
                        <div class="search-result-teks">${escapeHtml(item.terjemahan)}</div>
                    </div>
                `;
            });
            searchResultsList.innerHTML = html;
        } else {
            searchResultsList.innerHTML = '<div class="error">Tidak ada hadits yang cocok dengan kata kunci tersebut.</div>';
        }
    } catch (err) {
        console.error(err);
        searchResultsList.innerHTML = `<div class="error">Gagal mencari: ${err.message}. Coba kitab lain atau kata kunci berbeda.</div>`;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}

showBtn.addEventListener('click', () => {
    currentBook = bookSelect.value;
    currentNumber = parseInt(numberInput.value) || 1;
    loadHadith(currentBook, currentNumber);
});

searchBtn.addEventListener('click', searchHadith);
resetSearchBtn.addEventListener('click', () => {
    keywordInput.value = '';
    searchResultsContainer.style.display = 'none';
    searchResultsList.innerHTML = '';
    bookSelect.value = 'bukhari';
    numberInput.value = '1';
    loadHadith('bukhari', 1);
});

loadHadith('bukhari', 1);
</script>

<?php include '../includes/footer.php'; ?>