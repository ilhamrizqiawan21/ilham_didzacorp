<?php
header('Content-Type: application/json');
error_reporting(0);
include 'config.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
if (strlen($keyword) < 2) {
    echo json_encode(['error' => 'Masukkan minimal 2 karakter']);
    exit;
}

$quran_dir = __DIR__ . '/assets/quran/surah/';
$results = [];
$limit = 30;
$count = 0;

// Helper fungsi untuk mendapatkan nama surah dari file (tanpa load semua data berulang)
function get_surah_name($dir, $surah_id) {
    $file = $dir . $surah_id . '.json';
    if (!file_exists($file)) return "Surah $surah_id";
    $data = json_decode(file_get_contents($file), true);
    if (!$data) return "Surah $surah_id";
    // Cek struktur: mungkin key string surah_id atau langsung name_latin
    if (isset($data[$surah_id]['name_latin'])) {
        return $data[$surah_id]['name_latin'];
    }
    return $data['name_latin'] ?? "Surah $surah_id";
}

// Loop semua surah 1-114
for ($surah = 1; $surah <= 114; $surah++) {
    if ($count >= $limit) break;
    $file = $quran_dir . $surah . '.json';
    if (!file_exists($file)) continue;
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    if (!$data) continue;
    
    // normalisasi data: jika data memiliki key numerik (surah id) ambil itu, atau langsung.
    if (isset($data[$surah])) {
        $surah_data = $data[$surah];
    } else {
        $surah_data = $data;
    }
    
    $arabic_texts = $surah_data['text'] ?? [];
    $translations = $surah_data['translations']['id']['text'] ?? [];
    if (!is_array($arabic_texts)) continue;
    
    $keyword_lower = mb_strtolower($keyword, 'UTF-8');
    foreach ($arabic_texts as $ayat_num => $arab) {
        if ($count >= $limit) break 2;
        $terjemahan = $translations[$ayat_num] ?? '';
        $arab_lower = mb_strtolower($arab, 'UTF-8');
        $trans_lower = mb_strtolower($terjemahan, 'UTF-8');
        if (strpos($arab_lower, $keyword_lower) !== false || strpos($trans_lower, $keyword_lower) !== false) {
            $results[] = [
                'surah' => $surah,
                'surah_name' => get_surah_name($quran_dir, $surah),
                'ayat' => $ayat_num,
                'arab' => mb_substr($arab, 0, 150) . '...',
                'terjemahan' => mb_substr($terjemahan, 0, 150) . '...'
            ];
            $count++;
        }
    }
}

echo json_encode([
    'results' => $results,
    'total' => $count,
    'keyword' => $keyword
]);