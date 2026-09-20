<?php

namespace App\Http\Requests\Producto;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalizar valores booleanos
        $this->merge([
            'aplica_iva' => filter_var($this->aplica_iva, FILTER_VALIDATE_BOOLEAN),
            'aplica_igtf' => filter_var($this->aplica_igtf, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $empresaId = $this->user()?->empresaActiva()?->id;

        return [
            'tipo' => ['required', 'string', 'in:producto,servicio'],
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'codigo_interno' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique('productos', 'codigo_interno')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('productos', 'nombre')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'unidad_medida' => ['required', 'string', 'max:30'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'stock_maximo' => ['nullable', 'numeric', 'min:0'],

            // Precios Multi-Moneda
            'precio_costo_usd' => ['nullable', 'numeric', 'min:0'],
            'precio_costo_bs' => ['nullable', 'numeric', 'min:0'],
            'precio_detal_usd' => ['nullable', 'numeric', 'min:0'],
            'precio_detal_bs' => ['nullable', 'numeric', 'min:0'],
            'precio_mayorista_usd' => ['nullable', 'numeric', 'min:0'],
            'precio_mayorista_bs' => ['nullable', 'numeric', 'min:0'],
            'tasa_cambio' => ['nullable', 'numeric', 'min:0.0001'],

            // Fiscal (IVA e IGTF)
            'aplica_iva' => ['required', 'boolean'],
            'iva_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'aplica_igtf' => ['required', 'boolean'],
            'igtf_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],

            // Códigos de barra opcionales
            'codigos_barra' => ['nullable', 'array'],
            'codigos_barra.*.codigo' => ['nullable', 'string', 'max:100'],
            'codigos_barra.*.descripcion' => ['nullable', 'string', 'max:100'],

            // Proveedores opcionales
            'proveedores' => ['nullable', 'array'],
            'proveedores.*.proveedor_id' => ['required_with:proveedores', 'integer'],
            'proveedores.*.codigo_proveedor' => ['nullable', 'string', 'max:100'],
            'proveedores.*.ultimo_costo_usd' => ['nullable', 'numeric', 'min:0'],
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
            'tipo.required' => 'Debe seleccionar si es un Producto o Servicio.',
            'categoria_id.required' => 'Debe seleccionar una categoría válida.',
            'categoria_id.exists' => 'La categoría seleccionada no existe o está inactiva.',
            'codigo_interno.required' => 'El código SKU o identificador interno es obligatorio.',
            'codigo_interno.unique' => 'Ya existe un producto con este código SKU en tu empresa.',
            'nombre.required' => 'El nombre del producto o servicio es obligatorio.',
            'nombre.unique' => 'Ya existe un producto registrado con este nombre en tu empresa.',
            'unidad_medida.required' => 'Debe seleccionar una unidad de medida.',
        ];
    }
}
