<?php

namespace Amplify\System\Traits;

use Amplify\ErpApi\Facades\ErpApi;

trait OrderEmailFormatingTrait
{
    private function replaceContentsForOrder(array $data, $key): array
    {
        $data[$key] = str_replace(
            '__web_order_number__',
            $data['order']->web_order_number,
            $data[$key]
        );

        $data[$key] = str_replace(
            '__erp_order_number__',
            ($data['order']->erp_order_id ?? ''),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__total_amount__',
            $data['order']->total_amount,
            $data[$key]
        );

        $data[$key] = str_replace(
            '__contact_name__',
            $data['order']->contact->name ?? '',
            $data[$key]
        );

        $data[$key] = str_replace(
            '__contact_email__',
            $data['order']->contact->email ?? '',
            $data[$key]
        );

        $data[$key] = str_replace(
            '__billing_address_line_1__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'CustomerAddress1', 'ShipToAddress1'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__billing_address_line_2__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'CustomerAddress2', 'ShipToAddress2'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__billing_city__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'BillToCity', 'ShipToCity'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__billing_state__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'BillToState', 'ShipToState'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__billing_zip_code__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'BillToZipCode', 'ShipToZipCode'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__billing_country__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'BillToCountry', 'ShipToCountry'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__shipping_address_line_1__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'ShipToAddress1'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__shipping_address_line_2__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'ShipToAddress2'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__shipping_city__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'ShipToCity'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__shipping_state__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'ShipToState'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__shipping_zip_code__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'ShipToZipCode'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__shipping_country__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'ShipToCountry'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__order_suffix__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'OrderSuffix'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__po_number__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'CustomerPurchaseOrdernumber'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__order_status__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'OrderStatus'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__order_type__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'OrderType'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__invoice_amount__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'InvoiceAmount'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__warehouse_code__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'WarehouseID'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__entry_date__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'EntryDate'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__estimate_ship_date__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'RequestedShipDate'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__order_details__',
            view(
                'system::email.order.details',
                [
                    'details' => !empty($data['order']->erp_details) ? $data['order']->erp_details['OrderDetail'] : [],
                    'warehouseCode' => $data['order']->erp_details['WarehouseID']
                ]
            )->render(),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__invoice_date__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'InvoiceDate'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__carrier_code__',
            $this->getOrderDataByKey(
                $data['order']->erp_details, 'CarrierCode'
            ),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__notes__',
            $this->getOrderNote($data),
            $data[$key]
        );

        $data[$key] = str_replace(
            '__customer_order_details_url__',
            '<a href="'.route('frontend.orders.show', $data['order']->erp_order_id).'">View Details</a>',
            $data[$key]
        );

        $data[$key] = str_replace(
            '__customer_quotation_details_url__',
            '<a href="'.route('frontend.quotations.show', $data['order']->erp_order_id).'">View Details</a>',
            $data[$key]
        );

        return $data;
    }
    private function getOrderDataByKey(array $data, $key, $fallBackKey = null): string
    {
        if (! empty($data[$key])) {
            return $data[$key];
        }

        if (!empty($fallBackKey) && ! empty($data[$fallBackKey])) {
            return $data[$fallBackKey];
        }

        return '';
    }

    private function getOrderNote(array $data): string
    {
        if (! empty($data['notes']))
        {
            return $data['notes'];
        }

        try {
            $noteList = ErpApi::getNotesList(['order_number' => $data['order']->erp_order_id]);

            if ($noteList->isNotEmpty()) {
                $parsedNote = '';
                foreach ($noteList as $note) {
                    if (! empty($note->SecureFlag) && $note->SecureFlag === true) {
                        continue;
                    }

                    $text = trim($note->Note ?? '');
                    if ($text === '') {
                        continue;
                    }

                    $parsedNote = trim($text);
                }
                return $parsedNote;
            }
        } catch (\Exception $e) {
            return '';
        }

        return '';
    }
}


