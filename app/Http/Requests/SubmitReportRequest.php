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
}
