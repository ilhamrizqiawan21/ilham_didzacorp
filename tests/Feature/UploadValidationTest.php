<?php

namespace Tests\Feature;

use App\Http\Controllers\Siswa\TugasController;
use App\Models\KelasMapel;
use App\Models\Siswa;
use App\Models\Tugas;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UploadValidationTest extends TestCase
{
    public function test_tugas_upload_accepts_valid_jpeg_png_and_pdf_files(): void
    {
        $fileRule = str_replace('nullable', 'required', TugasController::uploadFileRules());

        $validator = Validator::make([
            'files' => [
                UploadedFile::fake()->image('KTP_Ilham.jpeg'),
                UploadedFile::fake()->image('bukti.png'),
                UploadedFile::fake()->create('jawaban.pdf', 10, 'application/pdf'),
            ],
        ], [
            'files' => 'required|array|max:5',
            'files.*' => $fileRule,
        ]);

        $this->assertTrue($validator->passes(), $validator->errors()->first());
    }

    public function test_tugas_upload_rejects_disallowed_document_formats(): void
    {
        $user = new User;
        $siswa = new Siswa;
        $siswa->kelas_id = 10;
        $user->setRelation('siswa', $siswa);

        Auth::shouldReceive('user')->andReturn($user);

        $kelasMapel = new KelasMapel;
        $kelasMapel->kelas_id = 10;
        $kelasMapel->guru_id = 1;

        $tugas = new Tugas;
        $tugas->id = 1;
        $tugas->setRelation('kelasMapel', $kelasMapel);

        $request = new Request;
        $request->files->add([
            'file_upload' => UploadedFile::fake()->create(
                'jawaban.docx',
                100,
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ),
        ]);

        $controller = new TugasController;

        $this->expectException(ValidationException::class);
        $controller->store($request, $tugas);
    }

    public function test_tugas_upload_accepts_png_files(): void
    {
        $fileRule = str_replace('nullable', 'required', TugasController::uploadFileRules());
        $validator = Validator::make([
            'file' => UploadedFile::fake()->image('jawaban.png'),
        ], ['file' => $fileRule]);

        $this->assertTrue($validator->passes(), $validator->errors()->first());
    }
}
