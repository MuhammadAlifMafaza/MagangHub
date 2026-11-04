<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class SaveAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Lecturer && $this->user()->can('assess', $this->route('report'));
    }

    public function rules(): array
    {
        return ['score' => ['required', 'numeric', 'between:0,100'], 'notes' => ['nullable', 'string', 'max:10000']];
    }
}
