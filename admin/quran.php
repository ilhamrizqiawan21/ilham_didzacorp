<?php
include '../config.php';
include '../includes/fungsi.php';
cek_login('admin');
$title = 'Mushaf Al-Qur\'an (Khat Utsmani)';
include '../includes/header.php';

$quranFile = __DIR__ . '/../assets/data/quran.json';
if (!file_exists($quranFile)) {
    echo '<div class="form-container"><div class="error">File data Al-Qur\'an tidak ditemukan. Upload quran.json ke folder assets/data/</div></div>';
    include '../includes/footer.php';
    exit;
}

$quranData = json_decode(file_get_contents($quranFile), true);
if (!$quranData || !isset($quranData['quran'])) {
    echo '<div class="form-container"><div class="error">Format JSON tidak valid</div></div>';
    include '../includes/footer.php';
    exit;
}

// Kelompokkan ayat per surat
$surahs = [];
foreach ($quranData['quran'] as $ayah) {
    $surah = $ayah['surah'];
    if (!isset($surahs[$surah])) {
        $surahs[$surah] = [
            'name' => getSurahName($surah),
            'ayas' => []
        ];
    }
    $surahs[$surah]['ayas'][] = $ayah;
}

$surahList = [
    1 => 'Al-Fatihah', 2 => 'Al-Baqarah', 3 => "Ali 'Imran", 4 => 'An-Nisa', 5 => 'Al-Ma\'idah',
    6 => 'Al-An\'am', 7 => 'Al-A\'raf', 8 => 'Al-Anfal', 9 => 'At-Tawbah', 10 => 'Yunus',
    11 => 'Hud', 12 => 'Yusuf', 13 => 'Ar-Ra\'d', 14 => 'Ibrahim', 15 => 'Al-Hijr',
    16 => 'An-Nahl', 17 => 'Al-Isra', 18 => 'Al-Kahf', 19 => 'Maryam', 20 => 'Taha',
    21 => 'Al-Anbiya', 22 => 'Al-Hajj', 23 => 'Al-Mu\'minun', 24 => 'An-Nur', 25 => 'Al-Furqan',
    26 => 'Ash-Shu\'ara', 27 => 'An-Naml', 28 => 'Al-Qasas', 29 => 'Al-\'Ankabut', 30 => 'Ar-Rum',
    31 => 'Luqman', 32 => 'As-Sajdah', 33 => 'Al-Ahzab', 34 => 'Saba', 35 => 'Fatir',
    36 => 'Yasin', 37 => 'As-Saffat', 38 => 'Sad', 39 => 'Az-Zumar', 40 => 'Ghafir',
    41 => 'Fussilat', 42 => 'Ash-Shura', 43 => 'Az-Zukhruf', 44 => 'Ad-Dukhan', 45 => 'Al-Jathiyah',
    46 => 'Al-Ahqaf', 47 => 'Muhammad', 48 => 'Al-Fath', 49 => 'Al-Hujurat', 50 => 'Qaf',
    51 => 'Adh-Dhariyat', 52 => 'At-Tur', 53 => 'An-Najm', 54 => 'Al-Qamar', 55 => 'Ar-Rahman',
    56 => 'Al-Waqi\'ah', 57 => 'Al-Hadid', 58 => 'Al-Mujadila', 59 => 'Al-Hashr', 60 => 'Al-Mumtahanah',
    61 => 'As-Saff', 62 => 'Al-Jumu\'ah', 63 => 'Al-Munafiqun', 64 => 'At-Taghabun', 65 => 'At-Talaq',
    66 => 'At-Tahrim', 67 => 'Al-Mulk', 68 => 'Al-Qalam', 69 => 'Al-Haqqah', 70 => 'Al-Ma\'arij',
    71 => 'Nuh', 72 => 'Al-Jinn', 73 => 'Al-Muzzammil', 74 => 'Al-Muddaththir', 75 => 'Al-Qiyamah',
    76 => 'Al-Insan', 77 => 'Al-Mursalat', 78 => 'An-Naba', 79 => 'An-Nazi\'at', 80 => 'Abasa',
    81 => 'At-Takwir', 82 => 'Al-Infitar', 83 => 'Al-Mutaffifin', 84 => 'Al-Inshiqaq', 85 => 'Al-Buruj',
    86 => 'At-Tariq', 87 => 'Al-A\'la', 88 => 'Al-Ghashiyah', 89 => 'Al-Fajr', 90 => 'Al-Balad',
    91 => 'Ash-Shams', 92 => 'Al-Layl', 93 => 'Ad-Duha', 94 => 'Ash-Sharh', 95 => 'At-Tin',
    96 => 'Al-\'Alaq', 97 => 'Al-Qadr', 98 => 'Al-Bayyinah', 99 => 'Az-Zalzalah', 100 => 'Al-\'Adiyat',
    101 => 'Al-Qari\'ah', 102 => 'At-Takathur', 103 => 'Al-\'Asr', 104 => 'Al-Humazah', 105 => 'Al-Fil',
    106 => 'Quraysh', 107 => 'Al-Ma\'un', 108 => 'Al-Kawthar', 109 => 'Al-Kafirun', 110 => 'An-Nasr',
    111 => 'Al-Masad', 112 => 'Al-Ikhlas', 113 => 'Al-Falaq', 114 => 'An-Nas'
];

function getSurahName($surah) {
    global $surahList;
    return $surahList[$surah] ?? 'Unknown';
}

$selectedSurah = isset($_GET['surah']) ? (int)$_GET['surah'] : 1;
$startAyah = isset($_GET['start']) ? (int)$_GET['start'] : 1;
$totalAyat = isset($surahs[$selectedSurah]) ? count($surahs[$selectedSurah]['ayas']) : 0;
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');
.quran-text {
    font-family: 'Amiri', 'Scheherazade New', 'Traditional Arabic', serif;
}
.ayah {
    transition: background 0.2s;
    margin-bottom: 1.2rem;
    padding: 0.8rem;
    border-radius: 12px;
    background: #ffffff;
    border: 1px solid #eef2ff;
}
.ayah:hover {
    background: #fefce8;
    border-color: #d9f99d;
}
.ayah-number {
    display: inline-block;
    background: #10b981;
    color: white;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    text-align: center;
    line-height: 28px;
    font-size: 0.8rem;
    margin-left: 10px;
}
.arab-text {
    font-size: 1.5rem;
    line-height: 2rem;
    direction: rtl;
    margin: 0.5rem 0;
}
.translation-text {
    font-size: 0.85rem;
    color: #4b5563;
    text-align: left;
    border-top: 1px dashed #e2e8f0;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
}
.search-result-item {
    background: white;
    border-radius: 16px;
    padding: 0.8rem;
    margin-bottom: 0.8rem;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.2s;
}
.search-result-item:hover {
    background: #f0fdf4;
    transform: translateX(5px);
    border-color: #86efac;
}
.search-result-surah {
    font-weight: 700;
    color: #059669;
    margin-bottom: 0.25rem;
}
.search-result-text {
    font-size: 0.9rem;
    color: #1f2937;
    direction: rtl;
    text-align: right;
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-quran"></i> Mushaf Al-Qur'an</h2>
    <p class="page-subtitle">Khat Utsmani | Baca, cari, dan navigasi surat</p>
</div>

<!-- Pencarian Full Teks -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-search"></i> Pencarian Seluruh Al-Qur'an</div>
    <div class="form-row">
        <div class="form-group" style="flex: 4;">
            <input type="text" id="fullSearchInput" class="form-input" placeholder="Cari kata kunci (Arab atau Terjemahan)...">
        </div>
        <div class="form-group">
            <button id="fullSearchBtn" class="btn btn-primary"><i class="fas fa-search"></i> Cari Seluruh Quran</button>
        </div>
    </div>
    <div id="searchResultsArea" style="display: none; margin-top: 1rem;"></div>
</div>

<!-- Form Navigasi Surat -->
<div class="form-container">
    <div class="form-title"><i class="fas fa-list"></i> Navigasi Surat</div>
    <div class="form-row">
        <div class="form-group">
            <label>Pilih Surat</label>
            <select id="surahSelect" class="form-select">
                <?php for ($i = 1; $i <= 114; $i++): ?>
                    <option value="<?= $i ?>" <?= $selectedSurah == $i ? 'selected' : '' ?>><?= $i ?>. <?= getSurahName($i) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Mulai dari ayat</label>
            <input type="number" id="ayahStart" class="form-input" value="<?= $startAyah ?>" min="1" max="<?= $totalAyat ?>">
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <button id="goButton" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Tampilkan</button>
        </div>
    </div>
</div>

<!-- Area Tampilan Quran -->
<div class="form-container" id="quranDisplay">
    <div class="form-title"><i class="fas fa-book-open"></i> Surat <?= getSurahName($selectedSurah) ?></div>
    <div class="quran-text">
        <?php
        if (isset($surahs[$selectedSurah])) {
            $ayas = $surahs[$selectedSurah]['ayas'];
            $total = count($ayas);
            $start = max(1, min($startAyah, $total));
            for ($i = $start - 1; $i < $total; $i++) {
                $ayah = $ayas[$i];
                echo '<div class="ayah" data-surah="' . $selectedSurah . '" data-ayah="' . ($i+1) . '">';
                echo '<span class="ayah-number">' . ($i+1) . '</span>';
                echo '<div class="arab-text">' . $ayah['text'] . '</div>';
                if (isset($ayah['translation']) && !empty($ayah['translation'])) {
                    echo '<div class="translation-text">' . htmlspecialchars($ayah['translation']) . '</div>';
                }
                echo '</div>';
            }
        } else {
            echo '<p>Surat tidak ditemukan.</p>';
        }
        ?>
    </div>
    <div class="btn-group" style="margin-top: 1rem;">
        <?php if ($startAyah > 1): ?>
            <a href="?surah=<?= $selectedSurah ?>&start=<?= max(1, $startAyah - 10) ?>" class="btn btn-outline"><i class="fas fa-chevron-up"></i> Sebelumnya 10 ayat</a>
        <?php endif; ?>
        <?php if ($startAyah + 10 <= $totalAyat): ?>
            <a href="?surah=<?= $selectedSurah ?>&start=<?= $startAyah + 10 ?>" class="btn btn-outline">Berikutnya 10 ayat <i class="fas fa-chevron-down"></i></a>
        <?php endif; ?>
    </div>
</div>

<script>
// Navigasi surat
document.getElementById('goButton').addEventListener('click', function() {
    let surah = document.getElementById('surahSelect').value;
    let start = document.getElementById('ayahStart').value;
    window.location.href = '?surah=' + surah + '&start=' + start;
});

// Pencarian Full Teks via AJAX
const searchInput = document.getElementById('fullSearchInput');
const searchBtn = document.getElementById('fullSearchBtn');
const searchResultsArea = document.getElementById('searchResultsArea');

searchBtn.addEventListener('click', function() {
    const keyword = searchInput.value.trim();
    if (keyword.length < 2) {
        alert('Masukkan minimal 2 karakter');
        return;
    }
    searchResultsArea.style.display = 'block';
    searchResultsArea.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-pulse"></i> Mencari...</div>';
    
    fetch(`search_quran?keyword=${encodeURIComponent(keyword)}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                searchResultsArea.innerHTML = `<div class="error">${data.error}</div>`;
                return;
            }
            if (data.results && data.results.length > 0) {
                let html = `<div class="form-title"><i class="fas fa-list"></i> Hasil pencarian "${data.keyword}" (${data.total} ditemukan)</div>`;
                data.results.forEach(item => {
                    html += `
                        <div class="search-result-item" onclick="goToAyah(${item.surah}, ${item.ayat})">
                            <div class="search-result-surah">📖 ${item.surah}. ${item.surah_name} : Ayat ${item.ayat}</div>
                            <div class="search-result-text">${item.text}</div>
                            <div style="font-size:0.8rem; color:#4b5563;">${item.translation}</div>
                        </div>
                    `;
                });
                searchResultsArea.innerHTML = html;
            } else {
                searchResultsArea.innerHTML = '<div class="error">Tidak ditemukan ayat yang sesuai.</div>';
            }
        })
        .catch(err => {
            searchResultsArea.innerHTML = `<div class="error">Gagal mencari: ${err.message}</div>`;
        });
});

// Fungsi untuk langsung ke surat/ayat tertentu
function goToAyah(surah, ayah) {
    window.location.href = `?surah=${surah}&start=${ayah}`;
}

// Jika ada parameter start di URL, scroll ke ayat yang dimaksud
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const targetAyah = urlParams.get('start');
    if (targetAyah) {
        setTimeout(() => {
            const ayahElement = document.querySelector(`.ayah[data-ayah="${targetAyah}"]`);
            if (ayahElement) {
                ayahElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                ayahElement.style.backgroundColor = '#fef3c7';
                setTimeout(() => { ayahElement.style.backgroundColor = ''; }, 2000);
            }
        }, 500);
    }
});
</script>

<?php include '../includes/footer.php'; ?>