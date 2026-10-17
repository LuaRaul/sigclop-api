<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInternalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pedidos.criar');
    }

    public function rules(): array
    {
        return [

            'request_type' => ['required', 'string', 'in:vestuario,higiene,meios_oficina'],

            'department_id' => ['nullable', 'integer'],

            'assigned_approver_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],

            'priority' => [
                'required',
                'string',
                'max:50'
            ],

            'justification' => [
                'nullable',
                'string'
            ],

            'needed_at' => [
                'nullable',
                'date'
            ],

            'items' => [
                'required',
                'array',
                'min:1'
            ],

            'items.*.product_id' => [
                'nullable',
                'integer'
            ],

            'items.*.description' => [
                'nullable',
                'string'
            ],

            'items.*.quantity_requested' => [
                'required',
                'integer',
                'min:1'
            ],

            'items.*.notes' => [
                'nullable',
                'string'
            ],

        ];
    }
}
