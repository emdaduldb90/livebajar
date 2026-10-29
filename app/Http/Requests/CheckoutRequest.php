<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['nullable', 'email:rfc,dns', 'max:190'],
            'phone' => ['required', 'string', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'shipping_zone' => ['required', Rule::in(array_keys(config('store.shipping_zones', [])))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', Rule::in(['cod', 'bkash', 'card'])],
        ];
    }
}
