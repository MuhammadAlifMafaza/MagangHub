<?php

namespace App\Http\Requests;

use App\Models\WeeklyReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class SubmitReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submit', [WeeklyReport::class, $this->route('period')]) ?? false;
    }

    public function rules(): array
    {
        $maxKilobytes = (int) ceil(config('magang.reports.max_size_bytes', 10 * 1024 * 1024) / 1024);

        return [
            'file' => ['required', 'file', 'extensions:pdf,docx', File::types(['pdf', 'docx'])->max($maxKilobytes)],
            'student_note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Berkas laporan wajib diunggah.',
            'file.file' => 'Berkas laporan yang dipilih tidak valid.',
            'file.extensions' => 'Berkas laporan harus berformat PDF atau DOCX.',
            'file.max' => 'Ukuran berkas laporan tidak boleh lebih dari :max kilobita.',
            'student_note.max' => 'Catatan untuk dosen tidak boleh lebih dari :max karakter.',
        ];
    }

    public function attributes(): array
    {
        return [
            'file' => 'berkas laporan',
            'student_note' => 'catatan untuk dosen',
        ];
    }
}
