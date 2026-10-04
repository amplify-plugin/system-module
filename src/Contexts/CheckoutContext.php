<?php

namespace Amplify\System\Contexts;

use Amplify\System\Backend\Models\CustomerOrder;
use Amplify\System\Backend\Models\CustomerOrderLine;
use Amplify\System\Backend\Models\CustomerOrderNote;

class CheckoutContext
{
    public array $resolved = [];
    public array $calculated = [];
    public array $errors = [];
    public array $meta = [];

    public function __construct(
        public array $payload
    )
    {
    }

    public function order(): CustomerOrder
    {
        $attributes = [];

        // `id`, `order_name`, `order_type`, `order_status`, `order_warehouse`, `order_warehouse_id`, `approver_id`,
        // `approval_status`, `quote_price_update`, `web_order_number`, `customer_id`, `customer_name`,
        // `customer_code`, `customer_address_1`, `customer_address_2`, `customer_address_3`, `customer_city`,
        // `customer_state`, `customer_country_code`, `customer_zip_code`, `contact_id`, `name`, `email`, `phone`,
        // `po_number`, `user_id`, `submitter_type`, `submitter_id`, `total_net_price`, `total_tax_amount`,
        // `total_shipping_cost`, `ship_to_method`, `ship_to_id`, `ship_to_method_label`, `ship_to_number`,
        // `ship_to_contact`, `ship_to_additional`, `ship_to_phone`, `pay_to_method`, `pay_to_address`, `pay_to_city`,
        // `pay_to_state`, `pay_to_country_code`, `pay_to_zip_code`, `pay_to_additional`, `ship_to_address`,
        // `ship_to_city`, `ship_to_state`, `ship_to_zip_code`, `ship_to_country_code`, `ship_to_instruction`,
        // `total_amount`, `total_additional`, `erp_order_id`, `erp_log`, `created_at`, `updated_at`

        $attributes['order_type'] = $this->payload['checkout']['type'] == 'order' ? CustomerOrder::IS_ORDER_TYPE : CustomerOrder::IS_RFQ_TYPE;
        $attributes['order_status'] = 'Pending';
        $attributes['approval_status'] = 'pending';
        $attributes['quote_price_update'] = false;
        $attributes['web_order_number'] = null;
        $attributes['customer_id'] = $this->resolved['customer']?->id ?? null;
        $attributes['contact_id'] = $this->resolved['contact']?->id ?? null;
        $attributes['customer_order_number'] = $this->payload['checkout']['customer_order_number'] ?? null;

        $attributes['user_id'] = '';  //suggest sales person id
        $attributes['approver_id'] = $this->payload['checkout']['approver_id'] ?? null;

        $attributes['submitted_at'] = \now();
        $attributes['total_net_price'] = $this->payload['amounts']['subtotal'] ?? null;
        $attributes['total_tax_amount'] = $this->payload['amounts']['tax'] ?? null;
        $attributes['total_shipping_cost'] = $this->payload['amounts']['shipping'] ?? null;
        $attributes['total_amount'] = $this->payload['amounts']['total'] ?? null;

        $attributes['temp_address'] = $this->payload['checkout']['temp_address'] ?? null; // use-case

        $attributes['spare_1'] = $this->payload['checkout']['spare_1'] ?? null;
        $attributes['spare_2'] = $this->payload['checkout']['spare_2'] ?? null;

        $attributes['notes'] = $this->payload['checkout']['notes'] ?? null; //use-case

        $attributes['draft_name'] = $this->payload['checkout']['draft_name'] ?? null;

        $attributes['erp_order_id'] = $this->payload['checkout']['erp_order_id'] ?? null;

        $attributes['customer_name'] = $this->payload['customer']['name'] ?? null;
        $attributes['email'] = $this->payload['contact']['email'] ?? null;
        $attributes['phone'] = $this->payload['contact']['phone'] ?? null;

        $attributes['shipping_method'] = $this->payload['shipping']['method']['code'] ?? null;
        $attributes['shipping_number'] = $this->payload['shipping']['number'] ?? null;
        $attributes['ship_to_address'] = $this->payload['shipping']['ship_to_address'] ?? null;
        $attributes['ship_to_city'] = $this->payload['shipping']['ship_to_city'] ?? null;
        $attributes['ship_to_state'] = $this->payload['shipping']['ship_to_state'] ?? null;
        $attributes['ship_to_zip_code'] = $this->payload['shipping']['zip_code'] ?? null;
        $attributes['ship_to_country_code'] = $this->payload['shipping']['ship_to_country_code'] ?? null;

        return new CustomerOrder($attributes);
    }

    public function items(): array
    {
        return [new CustomerOrderLine()];
    }

    public function notes()
    {
        return [new CustomerOrderNote()];
    }
}