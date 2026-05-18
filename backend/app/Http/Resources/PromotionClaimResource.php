<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionClaimResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'voucher_code' => $this->voucher_code,
            'status' => $this->status,
            'claimed_at' => $this->claimed_at,
            'redeemed_at' => $this->redeemed_at,
            'expires_at' => $this->expires_at,
            'promotion' => $this->whenLoaded('promotion', fn() => PromotionResource::make($this->promotion)),
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => trim($this->user->first_name . ' ' . $this->user->last_name),
                'phone' => $this->user->phone,
            ]),
        ];
    }
}
