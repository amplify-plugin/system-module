<?php

namespace Amplify\System\Pipelines\Checkout;

use Amplify\System\Backend\Models\CustomerOrder;
use Amplify\System\Backend\Models\SystemConfiguration;
use Amplify\System\Contexts\CheckoutContext;
use Closure;

final class CreateOrder
{
    public function handle(CheckoutContext $context, Closure $next): CheckoutContext
    {
        $attributes = [];

        $attributes['order_name'] = $context->payload['checkout']['order_name'] ?? null;

        $attributes['order_type'] = match ($context->payload['checkout']['type']) {
            'order' => CustomerOrder::IS_ORDER_TYPE,
            'quotation' => CustomerOrder::IS_RFQ_TYPE,
            'draft' => '0'
        };

        $attributes['order_status'] = 'Pending';

        $attributes['order_warehouse'] = null;
        $attributes['order_warehouse_id'] = null;
        $attributes['approver_id'] = null;
        $attributes['approval_status'] = 'Pending';
        $attributes['quote_price_update'] = false;
        $attributes['web_order_number'] = implode('', $this->getWebOrderNumber());

        // Customer Information
        $attributes['customer_id'] = $context->resolved['customer']?->id ?? $context->payload['customer']['id'] ?? null;
        $attributes['customer_name'] = $context->resolved['customer']?->customer_name ?? $context->payload['customer']['name'] ?? null;
        $attributes['customer_code'] = $context->resolved['customer']?->customer_code ?? $context->payload['customer']['number'] ?? null;
        $attributes['customer_address_1'] = $context->resolved['customer']?->address_1 ?? $context->payload['customer']['address1'] ?? null;
        $attributes['customer_address_2'] = $context->resolved['customer']?->address_2 ?? $context->payload['customer']['address2'] ?? null;
        $attributes['customer_address_3'] = $context->resolved['customer']?->address_3 ?? $context->payload['customer']['address3'] ?? null;
        $attributes['customer_city'] = $context->resolved['customer']?->city ?? $context->payload['customer']['city'] ?? null;
        $attributes['customer_state'] = $context->resolved['customer']?->state ?? $context->payload['customer']['state'] ?? null;
        $attributes['customer_country_code'] = $context->resolved['customer']?->country_code ?? $context->payload['customer']['country'] ?? null;
        $attributes['customer_zip_code'] = $context->resolved['customer']?->zip_code ?? $context->payload['customer']['zip_code'] ?? null;

        // Contact Information
        $attributes['contact_id'] = $context->resolved['contact']?->id ?? $context->payload['contact']['id'] ?? null;
        $attributes['name'] = $context->resolved['contact']?->name ?? $context->payload['contact']['name'] ?? null;
        $attributes['email'] = $context->resolved['contact']?->email ?? $context->payload['contact']['email'] ?? null;
        $attributes['phone'] = $context->resolved['contact']?->phone ?? $context->payload['contact']['phone'] ?? null;

        $attributes['po_number'] = $context->payload['checkout']['po_number'] ?? null;

        // Submitter Information
        $attributes['submitter_type'] = $context->resolved['submitter'] ? get_class($context->resolved['submitter']) : null;
        $attributes['submitter_id'] = $context->resolved['submitter'] ? $context->resolved['submitter']->id : null;

        // Order Summary Information
        $attributes['total_net_price'] = $context->calculated['subtotal'] ?? $context->payload['amounts']['subtotal'] ?? null;
        $attributes['total_tax_amount'] = $context->calculated['tax'] ?? $context->payload['amounts']['tax'] ?? null;
        $attributes['total_shipping_cost'] = $context->calculated['shipping'] ?? $context->payload['amounts']['shipping'] ?? null;
        $attributes['total_amount'] = $context->calculated['total'] ?? $context->payload['amounts']['total'] ?? null;
        $attributes['total_additional'] = $context->payload['amounts']['additional'] ?? [];

        // Shipping Information
        $attributes['ship_to_id'] = $context->resolved['shipping']?->id ?? $context->payload['shipping']['id'] ?? null;
        $attributes['ship_to_method'] = $context->payload['shipping']['method']['code'] ?? null;
        $attributes['ship_to_method_label'] = $context->payload['shipping']['method']['label'] ?? null;
        $attributes['ship_to_address'] = $context->resolved['shipping']?->address_name ?? $context->payload['shipping']['ship_to_address'] ?? null;
        $attributes['ship_to_name'] = $context->resolved['shipping']?->address_name ?? $context->payload['shipping']['name'] ?? null;
        $attributes['ship_to_number'] = $context->resolved['shipping']?->address_code ?? $context->payload['shipping']['number'] ?? null;
        $attributes['ship_to_city'] = $context->resolved['shipping']?->city ?? $context->payload['shipping']['city'] ?? null;
        $attributes['ship_to_state'] = $context->resolved['shipping']?->state ?? $context->payload['shipping']['state'] ?? null;
        $attributes['ship_to_zip_code'] = $context->resolved['shipping']?->zip_code ?? $context->payload['shipping']['zip_code'] ?? null;
        $attributes['ship_to_country_code'] = $context->resolved['shipping']?->country_code ?? $context->payload['shipping']['country'] ?? null;
        $attributes['ship_to_instruction'] = $context->payload['shipping']['instructions'] ?? null;
        $attributes['ship_to_contact'] = $context->payload['shipping']['contact'] ?? $context->resolved['contact']?->name ?? null;
        $attributes['ship_to_additional'] = $context->payload['shipping']['additional'] ?? [];
        $attributes['ship_to_phone'] = $context->resolved['shipping']?->phone ?? $context->payload['shipping']['phone'] ?? null;
        $attributes['ship_to_address_1'] = $context->resolved['shipping']?->address_1 ?? $context->payload['shipping']['address1'] ?? null;
        $attributes['ship_to_address_2'] = $context->resolved['shipping']?->address_2 ?? $context->payload['shipping']['address2'] ?? null;
        $attributes['ship_to_address_3'] = $context->resolved['shipping']?->address_3 ?? $context->payload['shipping']['address3'] ?? null;

        // Payment Information
        $attributes['pay_to_method'] = $context->payload['payment']['method'] ?? null;
        $attributes['pay_to_address'] = $context->payload['payment']['address'] ?? null;
        $attributes['pay_to_city'] = $context->payload['payment']['city'] ?? null;
        $attributes['pay_to_state'] = $context->payload['payment']['state'] ?? null;
        $attributes['pay_to_country_code'] = $context->payload['payment']['country'] ?? null;
        $attributes['pay_to_zip_code'] = $context->payload['payment']['zip_code'] ?? null;
        $attributes['pay_to_additional'] = $context->payload['payment']['additional'] ?? null;
        $attributes['request'] = request()->all();

        $context->resolved['order'] = CustomerOrder::create($attributes);

        return $next($context);
    }

    /**
     * @return array[$prefix, $webOrderNumber]
     */
    private function getWebOrderNumber(): array
    {
        $prefix = config('amplify.basic.web_order_prefix');

        if (config('amplify.basic.nxt_available_web_order_number') == null) {
            SystemConfiguration::setValue('basic', 'basic.nxt_available_web_order_number', '0000001');
        }

        return [$prefix, config('amplify.basic.nxt_available_web_order_number')];
    }


}
