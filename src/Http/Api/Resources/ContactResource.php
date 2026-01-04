<?php

namespace Amplify\System\Http\Api\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'contact_code' => $this->contact_code,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_image' => asset($this->profile_image),
            'order_limit' => $this->order_limit,
            'daily_budget_limit' => $this->daily_budget_limit,
            'monthly_budget_limit' => $this->monthly_budget_limit,
            'spend_today' => $this->spend_today,
            'spend_this_month' => $this->spend_this_month,
            'customer_detail' => $this->customer()->exists()
                ? [
                    'customer_code' => $this->customer->customer_code,
                    'customer_name' => $this->customer->customer_name,
                    'customer_phone' => $this->customer->phone,
                    'customer_email' => $this->customer->email,
                    'customer_type' => $this->customer->customer_type,
                ]
                : (new \stdClass),
        ];
    }
}
