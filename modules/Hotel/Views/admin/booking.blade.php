@extends('admin.layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{ __('All Bookings') }}</h1>
        </div>

        @include('admin.message')

        <div class="text-right">
            <p><i>{{ __('Total bookings fetched: :total', ['total' => count($bookings)]) }}</i></p>
        </div>

        <div class="mb-3 text-right">
            <a href="{{ request()->fullUrlWithQuery(['type' => 'b2b']) }}"
                class="btn btn-sm {{ request('type') === 'b2b' ? 'btn-primary' : 'btn-outline-primary' }}">
                B2B
            </a>

            <a href="{{ request()->fullUrlWithQuery(['type' => 'b2c']) }}"
                class="btn btn-sm {{ request('type') === 'b2c' ? 'btn-primary' : 'btn-outline-primary' }}">
                B2C
            </a>

            <a href="{{ url()->current() }}"
                class="btn btn-sm {{ request('type') ? 'btn-outline-secondary' : 'btn-secondary' }}">
                AUTO
            </a>
        </div>


        <div class="panel">
            <div class="panel-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Order ID') }}</th>
                                <th>{{ __('Guest') }}</th>
                                <th>{{ __('Invoice ID') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Created At') }}</th>
                                <th width="100px">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                <tr>
                                    <td>{{ $booking['order_id'] }}</td>
                                    <td>{{ $booking['client_name'] }}</td>
                                    <td>{{ $booking['invoice_id'] }}</td>
                                    <td>{{ $booking['status'] }}</td>
                                    <td>{{ \Carbon\Carbon::parse($booking['created_at'])->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if (!empty($booking['order_id']))
                                            <a href="{{ route('booking.admin.details', ['orderId' => $booking['order_id']]) }}"
                                                class="btn btn-sm btn-info">
                                                {{ __('Details') }}
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">{{ __('No bookings found') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
