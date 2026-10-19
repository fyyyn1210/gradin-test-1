<?php

namespace App\Http\Requests;

use App\Enums\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCourierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/', Rule::unique('couriers')->ignore($this->route('courier'))],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('couriers')->ignore($this->route('courier'))],
            'address' => ['nullable', 'string', 'max:1000'],
            'vehicleType' => ['required', Rule::enum(VehicleType::class)],
            'vehiclePlate' => ['nullable', 'string', 'max:20'],
            'level' => ['integer', 'between:1,5'],
            'isActive' => ['boolean'],
            'registeredAt' => ['date'],
        ];
    }

    public function courierData(): array
    {
        return collect($this->validated())
            ->mapWithKeys(fn (mixed $value, string $key) => [Str::snake($key) => $value])
            ->all();
    }
}
