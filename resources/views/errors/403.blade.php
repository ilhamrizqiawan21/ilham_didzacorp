<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    <main class="container py-5">
        <div class="text-center py-5">
            <div class="display-1 fw-bold">403</div>
            <h1 class="h3 mb-3">Akses Ditolak</h1>
            <p class="text-muted mb-4">Anda tidak memiliki izin untuk mengakses halaman atau data tersebut.</p>
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : url('/') }}" class="btn btn-primary">Kembali</a>
        </div>
    </main>
</body>
</html>
