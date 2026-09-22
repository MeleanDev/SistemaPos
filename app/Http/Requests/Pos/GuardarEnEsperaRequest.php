<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class GuardarEnEsperaRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cliente_id' => ['nullable', 'integer'],
            'tipo_venta' => ['nullable', 'string'],
            'nota_referencia' => ['nullable', 'string', 'max:150'],
            'total_usd' => ['nullable', 'numeric', 'min:0'],
            'total_bs' => ['nullable', 'numeric', 'min:0'],
            'items' => ['nullable', 'array'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nota_referencia.max' => 'La nota de referencia no debe superar los 150 caracteres.',
        ];
    }
}
