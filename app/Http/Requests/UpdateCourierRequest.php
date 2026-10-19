<?php

namespace App\Http\Requests;

class UpdateCourierRequest extends StoreCourierRequest
{
    public function rules(): array
    {
        return array_map(fn (array $rules) => ['sometimes', ...$rules], parent::rules());
    }
}
