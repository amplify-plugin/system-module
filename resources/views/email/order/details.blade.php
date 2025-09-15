@php
    $base = 'border:1px solid #000; padding:8px; text-align:left;';
    $right = 'text-align:right;';
@endphp

<table style="width:100%; border-collapse:collapse;">
    <thead>
        <tr>
            <th style="{{ $base }}">{{ __('Item#') }}</th>
            <th style="{{ $base }}">{{ __('Customer Item Details') }}</th>
            <th style="{{ $base }}">{{ __('Warehouse') }}</th>
            <th style="{{ $base }}">{{ __('Quantity Ordered') }}</th>
            <th style="{{ $base }}">{{ __('Quantity Shipped') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($details ?? [] as $item)
            <tr>
                <td style="{{ $base }};">{{ $item['ItemNumber'] }}</td>
                <td style="{{ $base }};">
                    <strong>{{ $item['ItemNumber'] }}</strong> <br>
                    {{ $item['ItemDescription1'] }} {{ $item['ItemDescription2'] }}
                </td>
                <td style="{{ $base }};">
                    {{ $item['ShipWhse'] ?? $warehouseCode }}
                </td>
                <td style="{{ $base }} {{ $right }};">
                    {{ $item['QuantityOrdered'] }}
                </td>
                <td style="{{ $base }} {{ $right }};">
                    {{ $item['QuantityShipped'] }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
