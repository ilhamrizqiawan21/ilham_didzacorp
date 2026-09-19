<?php

namespace App\Services\Reports;

use App\Models\TahunAjaran;

/**
 * Immutable context shared by PDF and Excel reports.
 * Keeps academic metadata in one object instead of rebuilding it in controllers.
 */
final class ReportContext
{
    public function __construct(
        public readonly ?TahunAjaran $tahunAjaran,
        public readonly string $semester,
        public readonly array $school,
    ) {
    }

    public function semesterLabel(): string
    {
        return match ((string) $this->semester) {
            '1', 'ganjil' => 'Ganjil',
            '2', 'genap' => 'Genap',
            default => ucfirst((string) $this->semester),
        };
    }

    public function academicYearLabel(): string
    {
        return (string) ($this->tahunAjaran?->tahun ?? '-');
    }

    public function metadata(): array
    {
        return [
            'tahun_ajaran' => $this->academicYearLabel(),
            'semester' => $this->semesterLabel(),
            'school' => $this->school,
        ];
    }
}
