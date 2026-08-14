<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveInternalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return
            $this->user()->can('pedidos.aprovar') ||
            $this->user()->can('pedidos.rejeitar');
    }

    public function rules(): array
    {
        return [

            'comment' => [
                'nullable',
                'string',
                'max:1000'
            ]

        ];
    }
}
