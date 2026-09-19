@include('errors.status', [
    'code' => 429,
    'title' => 'Terlalu banyak permintaan',
    'message' => 'Sistem menerima terlalu banyak aktivitas dari perangkat ini. Silakan tunggu sebentar lalu coba lagi.',
    'primary' => '#0891b2',
    'primaryDark' => '#0e7490',
    'icon' => 'clock',
])
