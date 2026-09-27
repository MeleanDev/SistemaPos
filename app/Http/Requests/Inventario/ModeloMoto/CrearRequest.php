<?php

namespace App\Http\Requests\Inventario\ModeloMoto;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CrearRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $empresaId = $this->empresaId();

        return [
            'referencia' => [
                'required',
                'string',
                'max:50',
                Rule::unique('modelos_motos', 'referencia')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'anio' => ['required', 'integer', 'min:1900', 'max:2099'],
            'color' => ['required', 'string', 'max:100'],
            'cilindrada' => ['required', 'string', 'max:50'],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'referencia.required' => 'La referencia o código de la moto es requerida.',
            'referencia.unique' => 'Ya existe un modelo de moto registrado con esta referencia para la empresa activa.',
            'marca.required' => 'La marca de la moto es requerida.',
            'modelo.required' => 'El modelo de la moto es requerido.',
            'anio.required' => 'El año del modelo es requerido.',
            'color.required' => 'El color de la moto es requerido.',
            'cilindrada.required' => 'La cilindrada de la moto es requerida.',
        ];
    }
}
