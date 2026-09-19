<?php

namespace App\Services;

use App\Models\Kelas;
use App\Models\Role;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\XLSX\Reader;

class SiswaImportService
{
    private const MAX_IMPORT_ROWS = 500;

    public function import(string $filePath): array
    {
        try {
            $rows = $this->readRows($filePath);
        } catch (OpenSpoutException $e) {
            return [
                'imported' => 0,
                'errors' => ['File Excel tidak dapat dibaca. Pastikan file menggunakan template .xlsx yang valid.'],
            ];
        }

        if ($rows['errors'] !== []) {
            return [
                'imported' => 0,
                'errors' => $rows['errors'],
            ];
        }

        $roleSiswa = Role::where('nama_role', 'siswa')->firstOrFail();

        DB::transaction(function () use ($rows, $roleSiswa) {
            foreach ($rows['data'] as $row) {
                $user = User::create([
                    'username' => $row['username'],
                    'password' => Hash::make($row['password'] ?: User::DEFAULT_PASSWORD),
                    'is_password_default' => $row['password'] === '' || $row['password'] === User::DEFAULT_PASSWORD,
                    'nama_lengkap' => $row['nama_lengkap'],
                    'nip_nis' => $row['nis'],
                    'jenis_kelamin' => $row['jenis_kelamin'] ?: null,
                    'role_id' => $roleSiswa->id,
                    'is_active' => ($row['status'] ?: 'aktif') === 'aktif'
                        && $this->parseBoolean($row['is_active'], true),
                ]);

                Siswa::create([
                    'user_id' => $user->id,
                    'nis' => $row['nis'],
                    'kelas_id' => (int) $row['kelas_id'],
                    'angkatan' => $row['angkatan'] ?: null,
                    'status' => $row['status'] ?: 'aktif',
                ]);
            }
        });

        return [
            'imported' => count($rows['data']),
            'errors' => [],
        ];
    }

    private function readRows(string $filePath): array
    {
        $reader = new Reader;
        $readerOpened = false;

        $dataRows = [];
        $errors = [];
        $seenUsernames = [];
        $seenNis = [];
        $headerChecked = false;

        try {
            $reader->open($filePath);
            $readerOpened = true;

            foreach ($reader->getSheetIterator() as $sheet) {
                if ($sheet->getIndex() !== 0) {
                    break;
                }

                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $values = $this->normalizeRow($row->toArray());
                    $values = array_slice(array_pad($values, count(SiswaTemplateService::HEADERS), ''), 0, count(SiswaTemplateService::HEADERS));

                    if (! $headerChecked) {
                        $headerChecked = true;
                        if ($values !== SiswaTemplateService::HEADERS) {
                            $errors[] = 'Header template tidak sesuai. Silakan unduh dan gunakan template terbaru.';
                            break 2;
                        }

                        continue;
                    }

                    if ($this->isEmptyRow($values)) {
                        continue;
                    }

                    if (count($dataRows) >= self::MAX_IMPORT_ROWS) {
                        $errors[] = 'Import dibatasi maksimal '.self::MAX_IMPORT_ROWS.' siswa per file. Pecah file menjadi beberapa bagian.';
                        break 2;
                    }

                    $rowData = array_combine(SiswaTemplateService::HEADERS, $values);
                    $rowData['nis'] = Str::upper($rowData['nis']);
                    $rowErrors = $this->validateRow($rowData, $rowIndex, $seenUsernames, $seenNis);

                    if ($rowErrors !== []) {
                        $errors = array_merge($errors, $rowErrors);

                        continue;
                    }

                    $seenUsernames[] = strtolower($rowData['username']);
                    $seenNis[] = $rowData['nis'];
                    $dataRows[] = $rowData;
                }

                break;
            }
        } finally {
            if ($readerOpened) {
                $reader->close();
            }
        }

        if (! $headerChecked) {
            $errors[] = 'File Excel kosong atau sheet template tidak ditemukan.';
        }

        if ($headerChecked && $dataRows === [] && $errors === []) {
            $errors[] = 'Tidak ada data siswa yang bisa diimport.';
        }

        return [
            'data' => $dataRows,
            'errors' => $errors,
        ];
    }

    private function normalizeRow(array $values): array
    {
        return array_map(function ($value) {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            return trim((string) $value);
        }, $values);
    }

    private function isEmptyRow(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    private function validateRow(array $data, int $rowIndex, array $seenUsernames, array $seenNis): array
    {
        $errors = [];
        $prefix = "Baris {$rowIndex}:";

        if ($data['username'] === '') {
            $errors[] = "{$prefix} username wajib diisi.";
        } elseif (strlen($data['username']) > 50) {
            $errors[] = "{$prefix} username maksimal 50 karakter.";
        } elseif (in_array(strtolower($data['username']), $seenUsernames, true)) {
            $errors[] = "{$prefix} username duplikat di file.";
        } elseif (User::where('username', $data['username'])->exists()) {
            $errors[] = "{$prefix} username sudah digunakan.";
        }

        if ($data['nama_lengkap'] === '') {
            $errors[] = "{$prefix} nama_lengkap wajib diisi.";
        } elseif (strlen($data['nama_lengkap']) > 100) {
            $errors[] = "{$prefix} nama_lengkap maksimal 100 karakter.";
        }

        if ($data['nis'] === '') {
            $errors[] = "{$prefix} nis wajib diisi.";
        } elseif (strlen($data['nis']) > 20) {
            $errors[] = "{$prefix} nis maksimal 20 karakter.";
        } elseif (in_array($data['nis'], $seenNis, true)) {
            $errors[] = "{$prefix} nis duplikat di file.";
        } elseif (Siswa::where('nis', $data['nis'])->exists()) {
            $errors[] = "{$prefix} nis sudah digunakan.";
        } elseif (User::where('username', $data['nis'])->exists()) {
            $errors[] = "{$prefix} nis sudah digunakan sebagai username akun lain.";
        }

        if ($data['kelas_id'] === '') {
            $errors[] = "{$prefix} kelas_id wajib diisi.";
        } elseif (! ctype_digit($data['kelas_id']) || ! Kelas::whereKey((int) $data['kelas_id'])->exists()) {
            $errors[] = "{$prefix} kelas_id tidak ditemukan.";
        }

        if ($data['password'] !== '' && strlen($data['password']) < 6) {
            $errors[] = "{$prefix} password minimal 6 karakter.";
        }

        if ($data['jenis_kelamin'] !== '' && ! in_array($data['jenis_kelamin'], ['L', 'P'], true)) {
            $errors[] = "{$prefix} jenis_kelamin harus L atau P.";
        }

        if ($data['status'] !== '' && ! in_array($data['status'], ['aktif', 'lulus', 'keluar'], true)) {
            $errors[] = "{$prefix} status harus aktif, lulus, atau keluar.";
        }

        if ($data['is_active'] !== '' && $this->parseBoolean($data['is_active'], null) === null) {
            $errors[] = "{$prefix} is_active harus 1/0, true/false, ya/tidak, atau aktif/nonaktif.";
        }

        return $errors;
    }

    private function parseBoolean(string $value, ?bool $default): ?bool
    {
        $value = strtolower(trim($value));

        if ($value === '') {
            return $default;
        }

        return match ($value) {
            '1', 'true', 'ya', 'yes', 'aktif' => true,
            '0', 'false', 'tidak', 'no', 'nonaktif' => false,
            default => null,
        };
    }
}
