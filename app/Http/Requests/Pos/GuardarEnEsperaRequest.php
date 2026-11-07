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
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->hasRole('SuperAdmin') || $user->canAny(['pos.preventa', 'ventas.crear']);
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
            'vendedor_id' => ['nullable', 'integer'],
            'cliente' => ['nullable', 'array'],
            'cliente.*' => ['nullable'],
            'tipo_venta' => ['nullable', 'string'],
            'nota_referencia' => ['nullable', 'string', 'max:150'],
            'total_usd' => ['nullable', 'numeric', 'min:0'],
            'total_bs' => ['nullable', 'numeric', 'min:0'],
            'carrito' => ['nullable', 'array'],
            'carrito.*' => ['nullable'],
            'items' => ['nullable', 'array'],
            'items.*' => ['nullable'],
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
