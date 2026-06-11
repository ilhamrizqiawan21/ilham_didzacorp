<?php
// Pastikan output hanya JSON
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Jangan tampilkan error di output

include '../config.php';

$book = isset($_GET['book']) ? $_GET['book'] : 'bukhari';
$number = isset($_GET['number']) ? (int)$_GET['number'] : 1;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$action = isset($_GET['action']) ? $_GET['action'] : 'detail';

// Mapping file JSON (pastikan nama file sesuai)
$file_map = [
    'bukhari' => '../assets/data/shahih_bukhari.json',
    'muslim' => '../assets/data/shahih_muslim.json',
    'abudaud' => '../assets/data/sunan_abu_dawud.json',
    'tirmidzi' => '../assets/data/sunan_tirmidzi.json',
    'nasai' => '../assets/data/sunan_nasai.json',
    'ibnumajah' => '../assets/data/sunan_ibnu_majah.json',
    'mustadrak' => '../assets/data/al-mustadrak.json',
    'ahmad' => '../assets/data/musnad_ahmad.json',
    'syafii' => '../assets/data/musnad_syafii.json',
    'malik' => '../assets/data/muwatha_malik.json',
    'ibnuhibban' => '../assets/data/shahih_ibnu_hibban.json',
    'ibnukhuzaimah' => '../assets/data/shahih_ibnu_khuzaimah.json',
    'darimi' => '../assets/data/sunan_darimi.json',
    'daruquthni' => '../assets/data/sunan_daruquthni.json',
];

if (!isset($file_map[$book])) {
    echo json_encode(['error' => 'Kitab tidak ditemukan']);
    exit;
}

$file = $file_map[$book];
if (!file_exists($file)) {
    echo json_encode(['error' => 'File data tidak ditemukan: ' . basename($file)]);
    exit;
}

// Baca file JSON
$content = file_get_contents($file);
if ($content === false) {
    echo json_encode(['error' => 'Gagal membaca file data']);
    exit;
}

$data = json_decode($content, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['error' => 'Format JSON tidak valid: ' . json_last_error_msg()]);
    exit;
}

if (!is_array($data)) {
    echo json_encode(['error' => 'Data tidak berbentuk array']);
    exit;
}

// ========== MODE PENCARIAN ==========
if ($action === 'search' && !empty($keyword)) {
    $results = [];
    $keywordLower = mb_strtolower($keyword, 'UTF-8');
    $maxResults = 20;
    $count = 0;
    
    foreach ($data as $item) {
        $arab = mb_strtolower($item['teks_arab'] ?? '', 'UTF-8');
        $terjemahan = mb_strtolower($item['terjemahan'] ?? '', 'UTF-8');
        
        if (strpos($arab, $keywordLower) !== false || strpos($terjemahan, $keywordLower) !== false) {
            $nomorStr = $item['nomor_hadits'] ?? '';
            preg_match('/#(\d+)/', $nomorStr, $matches);
            $nomor = $matches[1] ?? 0;
            $results[] = [
                'number' => $nomor,
                'teks' => mb_substr(strip_tags($item['teks_arab'] ?? ''), 0, 100) . '...',
                'terjemahan' => mb_substr(strip_tags($item['terjemahan'] ?? ''), 0, 100) . '...'
            ];
            $count++;
            if ($count >= $maxResults) break;
        }
    }
    
    echo json_encode([
        'results' => $results,
        'total' => $count
    ]);
    exit;
}

// ========== MODE DETAIL (default) ==========
$total = count($data);
$found = null;
foreach ($data as $item) {
    $nomorStr = $item['nomor_hadits'] ?? '';
    preg_match('/#(\d+)/', $nomorStr, $matches);
    $nomor = $matches[1] ?? 0;
    if ($nomor == $number) {
        $found = $item;
        break;
    }
}

if (!$found) {
    echo json_encode(['error' => "Hadits nomor $number tidak ditemukan (1-$total)"]);
    exit;
}

echo json_encode([
    'arab' => $found['teks_arab'] ?? '',
    'id' => $found['terjemahan'] ?? '',
    'total' => $total
]);
?>