<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Lecturer && $this->user()->can('review', $this->route('report'));
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['revision_requested', 'approved'])],
            'feedback' => ['nullable', 'required_if:decision,revision_requested', 'string', 'max:10000'],
        ];
    }
}
