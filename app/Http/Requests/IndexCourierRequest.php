<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'regex:/^[1-5](,[1-5])*$/'],
            'sort_by' => ['nullable', Rule::in(['name', 'registered_at'])],
            'sort_dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function levels(): array
    {
        return $this->filled('level')
            ? array_map('intval', explode(',', $this->validated('level')))
            : [];
    }
}
