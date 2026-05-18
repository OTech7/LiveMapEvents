<?php

namespace App\Http\Requests\Promotions;

use Illuminate\Foundation\Http\FormRequest;

class RedeemVoucherRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'voucher_code' => 'required|string|max:12',
        ];
    }
}
