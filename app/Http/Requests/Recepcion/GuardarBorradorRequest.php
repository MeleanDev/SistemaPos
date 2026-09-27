<?php

namespace App\Http\Requests\Recepcion;

use App\Http\Requests\BaseRequest;

class GuardarBorradorRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'borrador_id' => ['nullable', 'integer'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'proveedor_id' => ['nullable', 'integer'],
            'numero_documento' => ['nullable', 'string', 'max:100'],
            'total_unidades' => ['nullable', 'integer', 'min:0'],
            'datos_json' => ['required', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'datos_json.required' => 'La información del borrador es obligatoria.',
            'datos_json.array' => 'El formato de los datos del borrador no es válido.',
        ];
    }
}
