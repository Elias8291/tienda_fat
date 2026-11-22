<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'old_price' => ['nullable', 'numeric', 'gt:price', 'max:99999999'],
            'active' => ['boolean'],
            'images' => [$this->route('product') ? 'nullable' : 'required', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'focus' => ['nullable', 'array'],
            'focus.*.id' => ['integer'],
            'focus.*.x' => ['integer', 'between:0,100'],
            'focus.*.y' => ['integer', 'between:0,100'],
            'new_focus_x' => ['nullable', 'array'],
            'new_focus_x.*' => ['integer', 'between:0,100'],
            'new_focus_y' => ['nullable', 'array'],
            'new_focus_y.*' => ['integer', 'between:0,100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'price' => 'precio',
            'old_price' => 'precio anterior',
            'images' => 'fotos',
            'images.*' => 'foto',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'old_price.gt' => 'El precio anterior debe ser mayor que el precio actual.',
            'images.required' => 'Sube al menos una foto del producto.',
            'images.*.max' => 'Cada foto debe pesar menos de 4 MB.',
            'images.*.mimes' => 'Las fotos deben ser JPG, PNG o WEBP.',
        ];
    }
}
