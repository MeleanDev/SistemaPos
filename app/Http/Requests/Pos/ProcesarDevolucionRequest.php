<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ProcesarDevolucionRequest extends BaseRequest
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
        $empresaId = $this->empresaId();

        return [
            'venta_id' => [
                'required',
                'integer',
                Rule::exists('ventas', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)),
            ],
            'motivo' => ['required', 'string', 'max:255'],
            'tipo_reembolso' => ['nullable', 'string', 'in:sin_reembolso,efectivo,transferencia,credito_cliente'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.venta_detalle_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
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
            'venta_id.required' => 'La venta a devolver es obligatoria.',
            'venta_id.exists' => 'La venta especificada no pertenece a la empresa o no existe.',
            'motivo.required' => 'El motivo de la devolución es obligatorio.',
            'motivo.max' => 'El motivo no debe superar los 255 caracteres.',
            'items.required' => 'Debe seleccionar al menos un producto a devolver.',
            'items.min' => 'Debe seleccionar al menos un producto a devolver.',
            'items.*.venta_detalle_id.required' => 'El identificador del renglón es requerido.',
            'items.*.cantidad.required' => 'La cantidad a devolver es requerida.',
            'items.*.cantidad.min' => 'La cantidad a devolver debe ser mayor a 0.',
        ];
    }
}
