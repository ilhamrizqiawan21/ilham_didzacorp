<?php
include 'config.php';
include 'includes/fungsi.php';
$title = 'Mushaf Al-Qur\'an - Khat Utsmani';
include 'includes/header.php';

$quran_dir = __DIR__ . '/assets/quran/surah/';

// Fungsi load surah data dari file JSON
function load_surah($dir, $surah_id) {
    $file = $dir . $surah_id . '.json';
    if (!file_exists($file)) return null;
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    // Cek struktur: jika key numerik surah_id ada, gunakan itu
    if (isset($data[$surah_id])) {
        return $data[$surah_id];
    }
    return $data;
}

function extract_ayat($data) {
    $ayat_list = [];
    if (isset($data['text']) && is_array($data['text'])) {
        $translations = $data['translations']['id']['text'] ?? [];
        foreach ($data['text'] as $no => $arab) {
            $ayat_list[] = [
                'number' => $no,
                'arab' => $arab,
                'terjemahan' => $translations[$no] ?? ''
            ];
        }
        usort($ayat_list, function($a, $b) {
            return (int)$a['number'] - (int)$b['number'];
        });
    }
    return $ayat_list;
}

// Ambil parameter dari URL
$surah_id = isset($_GET['surah']) ? (int)$_GET['surah'] : 1;
$highlight_ayat = isset($_GET['ayat']) ? (int)$_GET['ayat'] : 0;
if ($surah_id < 1 || $surah_id > 114) $surah_id = 1;

$surah_data = load_surah($quran_dir, $surah_id);
$error = null;
$ayat_list = [];
$surah_info = ['latin' => '', 'arab' => '', 'ayat_count' => 0];

if ($surah_data) {
    $surah_info['latin'] = $surah_data['name_latin'] ?? '';
    $surah_info['arab'] = $surah_data['name'] ?? '';
    $surah_info['ayat_count'] = $surah_data['number_of_ayah'] ?? 0;
    $ayat_list = extract_ayat($surah_data);
    if (empty($ayat_list)) {
        $error = "Tidak dapat mengekstrak ayat dari file JSON.";
    }
} else {
    $error = "File JSON surah tidak ditemukan: $quran_dir$surah_id.json";
}

// Kumpulkan data semua surah untuk dropdown (nama latin dan arab)
$all_surahs = [];
for ($i = 1; $i <= 114; $i++) {
    $temp = load_surah($quran_dir, $i);
    if ($temp) {
        $all_surahs[$i] = [
            'latin' => $temp['name_latin'] ?? "Surah $i",
            'arab' => $temp['name'] ?? ''
        ];
    } else {
        $all_surahs[$i] = ['latin' => "Surah $i", 'arab' => ''];
    }
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Scheherazade+New:wght@400;700&display=swap');
.ayat-arab {
    font-family: 'Scheherazade New', 'Traditional Arabic', 'Amiri Quran', 'Amiri', serif;
    font-size: 1.8rem;
    line-height: 2.2rem;
    text-align: right;
    direction: rtl;
    font-weight: 500;
    margin: 0.5rem 0;
    color: #1e3a5f;
}
.ayat-card {
    background: #fef9e6;
    border-radius: 1rem;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
    border-right: 6px solid #10b981;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    transition: all 0.2s;
}
.ayat-card:hover {
    background: #fffaf0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.ayat-card.highlight {
    background: #fef3c7;
    border-right-color: #f59e0b;
    animation: fadeHighlight 2s ease-out;
}
@keyframes fadeHighlight {
    0% { background: #fde68a; }
    100% { background: #fef9e6; }
}
.ayat-number {
    display: inline-block;
    background: #10b981;
    color: white;
    width: 32px;
    height: 32px;
    text-align: center;
    line-height: 32px;
    border-radius: 50%;
    font-weight: bold;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
}
.ayat-translation {
    font-size: 0.9rem;
    color: #4b5563;
    border-top: 1px dashed #e5e7eb;
    padding-top: 0.5rem;
    margin-top: 0.5rem;
    font-style: italic;
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
.alert-danger {
    background: #fee2e2;
    color: #b91c1c;
    padding: 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
}
@media (max-width: 768px) {
    .ayat-arab { font-size: 1.4rem; line-height: 1.8rem; }
    .ayat-card { padding: 0.75rem 1rem; }
}
</style>

<div class="page-header">
    <h2 class="page-title"><i class="fas fa-quran"></i> Mushaf Al-Qur'an</h2>
    <p class="page-subtitle">Bacaan suci Al-Qur'an dengan khat standar Utsmani & terjemahan Bahasa Indonesia</p>
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

<!-- Navigasi Surah -->
<div class="form-container" style="margin-bottom: 1rem;">
    <form method="GET" class="form-row" style="align-items: flex-end;" id="surahForm">
        <div class="form-group">
            <label>Pilih Surah</label>
            <select name="surah" id="surahSelect" class="form-select">
                <?php for ($i = 1; $i <= 114; $i++): ?>
                    <option value="<?= $i ?>" <?= $surah_id == $i ? 'selected' : '' ?>>
                        <?= $i ?>. <?= htmlspecialchars($all_surahs[$i]['latin']) ?> - <?= htmlspecialchars($all_surahs[$i]['arab']) ?>
                    </option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Baca</button>
        </div>
    </form>
</div>

<?php if ($error): ?>
    <div class="form-container">
        <div class="alert-danger"><?= $error ?></div>
    </div>
<?php elseif (!empty($ayat_list)): ?>
<div class="form-container">
    <div class="form-title" style="justify-content: space-between;">
        <span><i class="fas fa-book-open"></i> <?= htmlspecialchars($surah_info['latin']) ?> - <?= htmlspecialchars($surah_info['arab']) ?></span>
        <span class="badge-hadir">Jumlah Ayat: <?= $surah_info['ayat_count'] ?></span>
    </div>
    <div class="mushaf-content">
        <?php foreach ($ayat_list as $ayat): 
            $highlight_class = ($highlight_ayat == $ayat['number']) ? 'highlight' : '';
        ?>
        <div class="ayat-card <?= $highlight_class ?>" data-ayat="<?= $ayat['number'] ?>">
            <div class="ayat-number"><?= $ayat['number'] ?></div>
            <div class="ayat-arab"><?= $ayat['arab'] ?></div>
            <?php if ($ayat['terjemahan']): ?>
            <div class="ayat-translation"><?= htmlspecialchars($ayat['terjemahan']) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="btn-group" style="justify-content: center; margin-top: 1rem;">
    <?php if ($surah_id > 1): ?>
        <a href="?surah=<?= $surah_id - 1 ?><?= $highlight_ayat ? '&ayat='.$highlight_ayat : '' ?>" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Surah Sebelumnya</a>
    <?php endif; ?>
    <?php if ($surah_id < 114): ?>
        <a href="?surah=<?= $surah_id + 1 ?><?= $highlight_ayat ? '&ayat='.$highlight_ayat : '' ?>" class="btn btn-outline">Surah Berikutnya <i class="fas fa-arrow-right"></i></a>
    <?php endif; ?>
</div>
<?php else: ?>
    <div class="form-container"><p>Tidak ada ayat yang dapat ditampilkan.</p></div>
<?php endif; ?>

<script>
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
    
    fetch(`search_quran_mushaf.php?keyword=${encodeURIComponent(keyword)}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                searchResultsArea.innerHTML = `<div class="error" style="color:red;">${data.error}</div>`;
                return;
            }
            if (data.results && data.results.length > 0) {
                let html = `<div class="form-title"><i class="fas fa-list"></i> Hasil pencarian "${data.keyword}" (${data.total} ditemukan)</div>`;
                data.results.forEach(item => {
                    html += `
                        <div class="search-result-item" onclick="goToAyah(${item.surah}, ${item.ayat})">
                            <div class="search-result-surah">📖 ${item.surah}. ${item.surah_name} : Ayat ${item.ayat}</div>
                            <div class="search-result-text">${item.arab}</div>
                            <div style="font-size:0.8rem; color:#4b5563;">${item.terjemahan}</div>
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

function goToAyah(surah, ayah) {
    window.location.href = `?surah=${surah}&ayat=${ayah}`;
}

// Jika ada parameter 'ayat' di URL, scroll ke ayat tersebut
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const targetAyah = urlParams.get('ayat');
    if (targetAyah) {
        setTimeout(() => {
            const ayahElement = document.querySelector(`.ayat-card[data-ayat="${targetAyah}"]`);
            if (ayahElement) {
                ayahElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                ayahElement.classList.add('highlight');
                setTimeout(() => {
                    ayahElement.classList.remove('highlight');
                }, 2000);
            }
        }, 500);
    }
});
</script>

<?php include 'includes/footer.php'; ?>