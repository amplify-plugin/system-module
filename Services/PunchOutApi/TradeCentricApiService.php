<?php

namespace Amplify\System\Services\PunchOutApi;

use Illuminate\Support\Facades\Http;

class TradeCentricApiService
{
    private $config;

    public function __construct()
    {
        $this->config = config('amplify.punchout.configurations.trade-centric');
    }

    private function get(string $url, $query = null): array
    {
        $response = Http::timeout(30)
            ->withoutVerifying()
            ->baseUrl($this->config['url'])
            ->get($url, $query);

        return $response->json();
    }

    private function post(string $url, array $payload = []): array
    {
        $response = Http::withoutVerifying()
            ->post(($this->config['url'].$url), $payload);

        return $response->json();
    }

    public function punchOutRequestToSupplier(array $attributes = [])
    {
        $params = [
            'pos' => $attributes['pos'] ?? null,
            'operation' => $attributes['operation'] ?? 'create',
            'return_url' => $attributes['pos'] ? 'https://connect.tradecentric.com/gateway/link/api/'.$attributes['pos'] : null,
            'params' => [
                'header' => [],
                'type' => $attributes['type'] ?? 'setuprequest',
                'mode' => $attributes['mode'] ?? 'production',
                'body' => [
                    'data' => [],
                    'contact' => [
                        'email' => $attributes['contact_email'],
                        'name' => $attributes['contact_name'],
                        'unique' => $attributes['contact_unique'] ?? null,
                    ],
                    'buyercookie' => $attributes['buyercookie'] ?? null,
                    'postform' => $attributes['postform'] ?? null,
                    'shipping' => [
                        'data' => [
                            'address_name' => $attributes['address_name'] ?? null,
                            'shipping_id' => $attributes['shipping_id'] ?? null,
                            'shipping_business' => $attributes['shipping_business'] ?? null,
                            'shipping_to' => $attributes['shipping_to'] ?? null,
                            'shipping_street' => $attributes['shipping_street'] ?? null,
                            'shipping_city' => $attributes['shipping_city'] ?? null,
                            'shipping_state' => $attributes['shipping_state'] ?? null,
                            'shipping_zip' => $attributes['shipping_zip'] ?? null,
                            'shipping_country' => $attributes['shipping_country'] ?? null,
                            'country_id' => $attributes['country_id'] ?? null,
                        ],
                    ],
                    'items' => [
                        [
                            'supplier_id' => $attributes['product_code'],
                            'supplierauxid' => $attributes['product_id'] ?? null,
                            'quantity' => $attributes['quantity'],
                        ],
                    ],
                ],
                'custom' => $attributes['custom'],
            ],
        ];

        $this->config['url'] = 'https://virtserver.swaggerhub.com/PunchOut2Go/PunchOut-Request-to-Supplier/1.0.0';
        $this->post('/punchout', $params);
    }

    public function punchOutReturnCartFromSupplier(array $attributes = [])
    {
        $params = [
            'body' => [
                'total' => $attributes['total'] ?? null,
                'currency' => $attributes['currency'] ?? null,
                'data' => [
                    'edit_mode' => $attributes['edit_mode'] ?? null,
                    'shipping' => $attributes['shipping'] ?? null,
                    'shipping_description' => $attributes['shipping_description'] ?? null,
                    'tax' => $attributes['tax'] ?? null,
                    'tax_description' => $attributes['tax_description'] ?? null,
                ],
                'items' => [
                    [
                        'quantity' => $attributes['quantity'] ?? null,
                        'supplierid' => $attributes['supplierid'] ?? null,
                        'supplierauxid' => $attributes['supplierauxid'] ?? null,
                        'description' => $attributes['description'] ?? null,
                        'unitprice' => $attributes['unitprice'] ?? null,
                        'currency' => $attributes['currency'] ?? null,
                        'uom' => $attributes['uom'] ?? null,
                        'classification' => $attributes['classification'] ?? null,
                        'classdomain' => $attributes['classdomain'] ?? null,
                        'language' => $attributes['language'] ?? null,
                        'data' => [
                            'manufacturer' => $attributes['manufacturer'] ?? null,
                            'manufacturer_id' => $attributes['manufacturer_id'] ?? null,
                            'lead_time' => $attributes['lead_time'] ?? null,
                            'image_url' => $attributes['image_url'] ?? null,
                            'is_catchweight' => $attributes['is_catchweight'] ?? null,
                        ],
                    ],
                ],
            ],
        ];

        $this->config['url'] = 'https://virtserver.swaggerhub.com/PunchOut2Go/PunchOut-Return-Cart-from-Supplier/1.0.0';
        $this->post('/gateway/link/api/id/{sessionKey}', $params);
    }

    public function purchaseOrderRequestToSupplier(array $attributes = [])
    {
        $params = [
            'punchout_session' => $attributes['punchout_session'] ?? null,
            'mode' => $attributes['mode'] ?? null,
            'shared_secret' => $attributes['shared_secret'] ?? null,
            'api_key' => $attributes['api_key'] ?? null,
            'store_code' => $attributes['store_code'] ?? null,
            'header' => [
                'from_domain' => $attributes['from_domain'] ?? null,
                'from_identity' => $attributes['from_identity'] ?? null,
                'to_domain' => $attributes['to_domain'] ?? null,
                'to_identity' => $attributes['to_identity'] ?? null,
                'shared_secret' => $attributes['shared_secret'] ?? null,
                'po_payload_id' => $attributes['po_payload_id'] ?? null,
                'po_order_id' => $attributes['po_order_id'] ?? null,
                'po_order_date' => $attributes['po_order_date'] ?? null,
                'po_order_type' => $attributes['po_order_type'] ?? null,
                'order_request_id' => $attributes['order_request_id'] ?? null,
            ],
            'details' => [
                'total' => $attributes['total'] ?? null,
                'currency' => $attributes['currency'] ?? null,
                'shipping' => $attributes['shipping'] ?? null,
                'shipping_description' => $attributes['shipping_description'] ?? null,
                'tax' => $attributes['tax'] ?? null,
                'tax_description' => $attributes['tax_description'] ?? null,
                'ship_to' => [
                    'address_id' => $attributes['shipping_address_id'] ?? null,
                    'address_name' => $attributes['shipping_address_name'] ?? null,
                    'deliver_to' => $attributes['shipping_deliver_to'] ?? null,
                    'streets' => $attributes['shipping_streets'] ?? null,
                    'city' => $attributes['shipping_city'] ?? null,
                    'state' => $attributes['shipping_state'] ?? null,
                    'postalcode' => $attributes['shipping_postalcode'] ?? null,
                    'country' => $attributes['shipping_country'] ?? null,
                    'country_code' => $attributes['shipping_country_code'] ?? null,
                    'email' => $attributes['shipping_email'] ?? null,
                    'phone' => $attributes['shipping_phone'] ?? null,
                ],
                'bill_to' => [
                    'address_id' => $attributes['billing_address_id'] ?? null,
                    'address_name' => $attributes['billing_address_name'] ?? null,
                    'deliver_to' => $attributes['billing_deliver_to'] ?? null,
                    'streets' => $attributes['billing_streets'] ?? null,
                    'city' => $attributes['billing_city'] ?? null,
                    'state' => $attributes['billing_state'] ?? null,
                    'postalcode' => $attributes['billing_postalcode'] ?? null,
                    'country' => $attributes['billing_country'] ?? null,
                    'country_code' => $attributes['billing_country_code'] ?? null,
                    'email' => $attributes['billing_email'] ?? null,
                    'phone' => $attributes['billing_phone'] ?? null,
                ],
                'contact' => [
                    'address_id' => $attributes['contact_address_id'] ?? null,
                    'address_name' => $attributes['contact_address_name'] ?? null,
                    'deliver_to' => $attributes['contact_deliver_to'] ?? null,
                    'streets' => $attributes['contact_streets'] ?? null,
                    'city' => $attributes['contact_city'] ?? null,
                    'state' => $attributes['contact_state'] ?? null,
                    'postalcode' => $attributes['contact_postalcode'] ?? null,
                    'country' => $attributes['contact_country'] ?? null,
                    'country_code' => $attributes['contact_country_code'] ?? null,
                    'email' => $attributes['contact_email'] ?? null,
                    'phone' => $attributes['contact_phone'] ?? null,
                ],
            ],
            'items' => [
                [
                    'line_number' => $attributes['line_number'] ?? null,
                    'requested_delivery_date' => $attributes['requested_delivery_date'] ?? null,
                    'quantity' => $attributes['quantity'] ?? null,
                    'supplier_id' => $attributes['supplier_id'] ?? null,
                    'supplier_aux_id' => $attributes['supplier_aux_id'] ?? null,
                    'unitprice' => $attributes['unitprice'] ?? null,
                    'currency' => $attributes['currency'] ?? null,
                    'description' => $attributes['description'] ?? null,
                    'uom' => $attributes['uom'] ?? null,
                    'comments' => $attributes['comments'] ?? null,
                    'session_key' => $attributes['session_key'] ?? null,
                    'cart_position' => $attributes['cart_position'] ?? null,
                    'extra_data' => $attributes['extra_data'] ?? null,
                ],
            ],
        ];

        $this->config['url'] = 'https://virtserver.swaggerhub.com/PunchOut2Go/Purchase-Order-Request-to-Supplier/1.0.0';
        $this->post('/purchaseorder', $params);
    }
}
