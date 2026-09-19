@include('errors.status', [
    'code' => 500,
    'title' => 'Terjadi kesalahan sistem',
    'message' => 'Tim kami sedang memeriksa kendala ini. Silakan coba lagi beberapa saat lagi.',
    'detail' => 'Permintaan tidak dapat diproses saat ini.',
    'primary' => '#dc2626',
    'primaryDark' => '#991b1b',
    'icon' => 'alert',
])
