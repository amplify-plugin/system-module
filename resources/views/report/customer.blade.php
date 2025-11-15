<table border="1" cellspacing="0">
    <thead>
    <tr>
        <th colspan="15" align="center" style="font-weight: 700; font-size: 18%">
            <b>Customer Registered Between
                ({{$startDate->format(config('amplify.basic.date_format'))}})
                To ({{$endDate->format(config('amplify.basic.date_format'))}})
            </b>
        </th>
    </tr>
    <tr>
        <th style="background-color: black; color: white; font-weight: 700">#</th>
        <th style="background-color: black; color: white; font-weight: 700">acc_usr_name</th>
        <th style="background-color: black; color: white; font-weight: 700">cust_cust_num</th>
        <th style="background-color: black; color: white; font-weight: 700">cust_cust_name</th>
        <th style="background-color: black; color: white; font-weight: 700">cust_cust_address1</th>
        <th style="background-color: black; color: white; font-weight: 700">cust_cust_address2</th>
        <th style="background-color: black; color: white; font-weight: 700">cust_cust_address3</th>
        <th style="background-color: black; color: white; font-weight: 700">cust_cust_city</th>
        <th style="background-color: black; color: white; font-weight: 700">cust_cust_state</th>
        <th style="background-color: black; color: white; font-weight: 700">cust_cust_postal_code</th>
        <th style="background-color: black; color: white; font-weight: 700">cust_cust_country</th>
        <th style="background-color: black; color: white; font-weight: 700">ctt_name</th>
        <th style="background-color: black; color: white; font-weight: 700">ctt_email</th>
        <th style="background-color: black; color: white; font-weight: 700">ctt_phone_num1</th>
        <th style="background-color: black; color: white; font-weight: 700">acc_dte_created</th>
    </tr>
    </thead>
    <tbody>
    @foreach($customers as $customer)
        <tr>
            <th style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d">
                {{ $loop->iteration }}
            </th>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d; width: 200px">
                {{ $customer->contact->name ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d">
                {{ $customer->customer_code  ?? ''}}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d; width: 200px">
                {{ $customer->customer_name ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d">
                {{ $customer->defaultAddress->address_1 ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d">
                {{ $customer->defaultAddress->address_2 ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d">
                {{ $customer->defaultAddress->address_3 ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d">
                {{ $customer->defaultAddress->city ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d">
                {{ $customer->defaultAddress->state ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d">
                {{ $customer->defaultAddress->zip_code ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d">
                {{ $customer->defaultAddress->country_code ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d; width: 200px">
                {{ $customer->contact->name ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d; width: 200px">
                {{ $customer->contact->email ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d">
                {{ $customer->contact->phone ?? '' }}
            </td>

            <td style="background-color: @if($loop->even) #e0e0e0 @else white @endif ; color: black; border: 1px solid #3d3d3d"
                date-format="{{config('amplify.basic.date_format')}}">
                {{ $customer->created_at->format(config('amplify.basic.date_format')) }}
            </td>

        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <th colspan="15" align="center" style="font-size: 18%; font-weight: 700; border: 1px solid #3d3d3d">
            <b>Total Customers: {{ $customers->count() }}</b>
        </th>
    </tr>
    </tfoot>
</table>
