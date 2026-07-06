@php
    $cell = 'border:1px solid #dee2e6; padding:8px; text-align:left;';
    $right = 'text-align:right;';
    $hazmatCharge = $order->getHazmatChargeFromJson();
@endphp

<table style="width:100%; border-collapse:collapse; margin-bottom: 24px;">
    <thead>
        <tr>
            <th style="{{ $cell }}">Product Code</th>
            <th style="{{ $cell }}">Name</th>
            <th style="{{ $cell }} {{ $right }}">Unit Price</th>
            <th style="{{ $cell }} {{ $right }}">Quantity</th>
            <th style="{{ $cell }} {{ $right }}">Sub Total</th>
            <th style="{{ $cell }}">Warehouse</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($order->orderLines as $line)
            <tr>
                <td style="{{ $cell }}">{{ $line->product_code }}</td>
                <td style="{{ $cell }}">{{ optional($line->product)->local_product_name ?? optional($line->product)->product_name ?? '' }}</td>
                <td style="{{ $cell }} {{ $right }}">{{ number_format((float) $line->customer_price, 2) }}</td>
                <td style="{{ $cell }} {{ $right }}">{{ $line->qty }}</td>
                <td style="{{ $cell }} {{ $right }}">{{ number_format((float) $line->qty * (float) $line->customer_price, 2) }}</td>
                <td style="{{ $cell }}">{{ optional($line->warehouse)->name ?? '' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="{{ $cell }}">No order lines found.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<table style="width:100%; max-width:500px; border-collapse:collapse;">
    <tr>
        <td style="{{ $cell }}"><strong>Total Net Price:</strong></td>
        <td style="{{ $cell }} {{ $right }}">{{ number_format((float) ($order->total_net_price ?? 0), 2) }}</td>
    </tr>
    <tr>
        <td style="{{ $cell }}"><strong>Shipping Option:</strong></td>
        <td style="{{ $cell }} {{ $right }}">{{ $order->shipping_method ?? '-' }}</td>
    </tr>
    <tr>
        <td style="{{ $cell }}"><strong>Total Tax Amount:</strong></td>
        <td style="{{ $cell }} {{ $right }}">{{ number_format((float) ($order->total_tax_amount ?? 0), 2) }}</td>
    </tr>
    <tr>
        <td style="{{ $cell }}"><strong>Total Shipping Cost:</strong></td>
        <td style="{{ $cell }} {{ $right }}">{{ number_format((float) ($order->total_shipping_cost ?? 0), 2) }}</td>
    </tr>
    <tr>
        <td style="{{ $cell }}"><strong>Hazmat Charge:</strong></td>
        <td style="{{ $cell }} {{ $right }}">{{ $hazmatCharge !== null ? number_format((float) $hazmatCharge, 2) : '-' }}</td>
    </tr>
    <tr>
        <td style="{{ $cell }}"><strong>Total Amount:</strong></td>
        <td style="{{ $cell }} {{ $right }}">{{ number_format((float) ($order->total_amount ?? 0), 2) }}</td>
    </tr>
</table>
