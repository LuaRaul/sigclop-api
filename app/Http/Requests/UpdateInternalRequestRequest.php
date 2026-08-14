<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInternalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pedidos.editar');
    }

    public function rules(): array
    {
        return [

            'department_id' => ['nullable','integer'],

            'assigned_approver_id' => [
                'nullable',
                'integer',
                'exists:users,id'
            ],

            'priority' => [
                'sometimes',
                'required',
                'string',
                'max:50'
            ],

            'status' => [
                'sometimes',
                'required',
                'string'
            ],

            'justification' => [
                'nullable',
                'string'
            ],

            'needed_at' => [
                'nullable',
                'date'
            ],

        ];
    }
}
