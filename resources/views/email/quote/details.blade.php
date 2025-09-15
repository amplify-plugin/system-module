<table style="width: 100%; border-collapse: collapse;">
    <thead>
    <tr>
        <th style="border: 1px solid #000; padding: 8px; text-align: left;">{{ __('Item') }}</th>
        <th style="border: 1px solid #000; padding: 8px; text-align: left;">{{ __('Description') }}</th>
        <th style="border: 1px solid #000; padding: 8px; text-align: left;">{{ __('Qty') }}</th>
        <th style="border: 1px solid #000; padding: 8px; text-align: left;">{{ __('UM') }}</th>
        <th style="border: 1px solid #000; padding: 8px; text-align: left;">{{ __('Price') }}</th>
        <th style="border: 1px solid #000; padding: 8px; text-align: left;">{{ __('UM') }}</th>
        <th style="border: 1px solid #000; padding: 8px; text-align: left;">{{ __('Total') }}</th>
    </tr>
    </thead>
    <tbody>
    @php $counter = 0 @endphp
    @foreach ($details ?? [] as $item)
        <tr>
            <td style="border: 1px solid #000; padding: 8px; text-align: left;">{{ $item->ItemType === 'I' ? 'Special' : '' }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: left;">{{ $item->ItemDescription1 }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: left;">{{ $item->QuantityOrdered }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: left;">{{ $item->UnitOfMeasure }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: right;">{{ price_format($item->ActualSellPrice) }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: left;">{{ $item->PricingUM }}</td>
            <td style="border: 1px solid #000; padding: 8px; text-align: left;">{{ price_format($item->TotalLineAmount) }}</td>
        </tr>
        @php $counter++ @endphp
    @endforeach
    </tbody>
</table>
