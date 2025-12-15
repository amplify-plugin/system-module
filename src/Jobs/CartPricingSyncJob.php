<?php

namespace Amplify\System\Jobs;

use Amplify\ErpApi\Facades\ErpApi;
use Amplify\ErpApi\Wrappers\ShippingLocation;
use Amplify\System\Backend\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CartPricingSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private ?Cart $cart;

    /**
     * Create a new job instance.
     */
    public function __construct($id, private readonly ?string $shipToCode = null)
    {
        $this->cart = Cart::find($id);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->cart) {

            $this->cart = $this->cart->load('cartItems');

            $cartItems = $this->cart->cartItems()->exists() ? $this->cart->cartItems : new Collection();

            // Reset Cart if no item exists
            if ($cartItems->isEmpty()) {
                $this->cart->sub_total = 0;
                $this->cart->total = 0;
                $this->cart->tax_amount = null;
                $this->cart->ship_charge = null;
                $this->cart->currency = config('amplify.basic.global_currency', 'USD');
                return;
            }

            if (ErpApi::enabled()) {
                $erpCustomer = ErpApi::getCustomerDetail([
                    'customer_number' => empty($this->cart->contact_id)
                        ? config('amplify.frontend.guest_default')
                        : $this->cart->contact->customer->erp_id
                ]);

                $shippingList = ErpApi::getCustomerShippingLocationList(['customer_number' => $erpCustomer->CustomerNumber]);

                /**
                 * @var ShippingLocation $shipTo
                 */
                $shipTo = $shippingList->firstWhere('ShipToNumber', $erpCustomer->DefaultShipTo);

                if (empty($shipTo)) {
                    $shipTo = $shippingList->first();
                }

                $orderInfo = [
                    'customer_number' => $erpCustomer->CustomerNumber,
                    'customer_default_warehouse' => $erpCustomer->DefaultWarehouse,
                    'shipping_method' => $erpCustomer->CarrierCode,
                    'customer_order_ref' => null,
                    'ship_to_number' => $shipTo?->ShipToNumber ?? '',
                    'ship_to_address1' => $shipTo?->ShipToAddress1 ?? '',
                    'ship_to_address2' => $shipTo?->ShipToAddress2 ?? '',
                    'ship_to_address3' => $shipTo?->ShipToAddress3 ?? '',
                    'ship_to_city' => $shipTo?->ShipToCity ?? '',
                    'ship_to_country_code' => $shipTo?->ShipToCountryCode ?? '',
                    'ship_to_state' => $shipTo?->ShipToState ?? '',
                    'ship_to_zip_code' => $shipTo?->ShipToZipCode ?? '',
                    'phone_number' => '',
                    'shipping_name' => $shipTo?->ShipToName ?? '',
                    'items' => $cartItems->map(function ($item) {
                        return [
                            'ItemNumber' => $item->product_code,
                            'WarehouseID' => $item->product_warehouse_code,
                            'OrderQty' => $item->quantity,
                            'UnitOfMeasure' => $item->uom,
                        ];
                    })->toArray()
                ];

                $orderTotal = ErpApi::getOrderTotal($orderInfo);
                $this->cart->sub_total = $orderTotal->TotalLineAmount;
                $this->cart->total = $orderTotal->TotalOrderValue;
                $this->cart->tax_amount = $orderTotal->SalesTaxAmount;
                $this->cart->ship_charge = $orderTotal->FreightAmount;
                $this->cart->currency = config('amplify.basic.global_currency', 'USD');

                if ($orderTotal->OrderLines->isNotEmpty()) {
                    foreach ($cartItems as $item) {
                        if ($erpItem = $orderTotal->OrderLines->firstWhere('ItemNumber', $item->product_code)) {
                            $item->unitprice = $erpItem->UnitPrice;
                            $item->subtotal = $erpItem->TotalLineAmount;
                            $item->save();
                        }
                    }
                }

            }

            else {
                $this->cart->sub_total = $this->cart->cartItems->sum('subtotal');
                $this->cart->total = $this->cart->subtotal;
                $this->cart->tax_amount = null;
                $this->cart->ship_charge = null;
                $this->cart->currency = config('amplify.basic.global_currency', 'USD');
            }

            $this->cart->save();
        }
    }
}
