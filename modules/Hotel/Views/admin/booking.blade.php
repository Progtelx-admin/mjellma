@extends('admin.layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{ __('All Bookings') }}</h1>
        </div>

        @include('admin.message')

        <div class="filter-div d-flex justify-content-between">
            <div class="col-left dropdown">
                <form method="get" action="{{ route('hotel.admin.booking.index') }}"
                    class="filter-form filter-form-right d-flex justify-content-end flex-column flex-sm-row" role="search">
                    <input type="text" name="s" value="{{ Request()->s }}"
                        placeholder="{{ __('Search by order ID or email') }}" class="form-control">
                    <button class="btn-info btn btn-icon btn_search" type="submit">{{ __('Search') }}</button>
                </form>
            </div>
        </div>

        <div class="text-right">
            <p><i>{{ __('Found :total items', ['total' => $bookings->total()]) }}</i></p>
        </div>

        <div class="panel">
            <div class="panel-body">
                <form action="" class="bravo-form-item">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="60px"><input type="checkbox" class="check-all"></th>
                                    <th>{{ __('Order ID') }}</th>
                                    <th>{{ __('Booked By') }}</th>
                                    <th>{{ __('User Email') }}</th>
                                    <th>{{ __('User Phone') }}</th>
                                    <th>{{ __('Payment') }}</th>
                                    <th>{{ __('Created At') }}</th>
                                    <th width="100px">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bookings as $booking)
                                    <tr>
                                        <td><input type="checkbox" name="ids[]" class="check-item"
                                                value="{{ $booking->id }}"></td>
                                        <td>{{ $booking->order_id }}</td>
                                        <td>{{ ucfirst($booking->booked_by) }}</td>
                                        <td>{{ $booking->user_email }}</td>
                                        <td>{{ $booking->user_phone }}</td>
                                        <td>{{ $booking->payment_amount }} {{ $booking->currency_code }}</td>
                                        <td>{{ display_date($booking->created_at) }}</td>
                                        <td>
                                            <a href="{{ route('booking.admin.details', ['orderId' => $booking->order_id]) }}"
                                                class="btn btn-sm btn-info">
                                                {{ __('Details') }}
                                            </a>

                                            @php
                                                $liveStatus = $statuses[$booking->order_id] ?? null;
                                            @endphp

                                            @if ($liveStatus && strtolower($liveStatus) !== 'cancelled' && !empty($booking->partner_order_id))
                                                <form method="POST"
                                                    action="{{ route('booking.admin.cancel', ['partnerOrderId' => $booking->partner_order_id]) }}"
                                                    style="display:inline;"
                                                    onsubmit="return confirm('{{ __('Are you sure to cancel this booking?') }}');">
                                                    @csrf
                                                    <button class="btn btn-sm btn-danger">{{ __('Cancel') }}</button>
                                                </form>
                                            @endif
                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9">{{ __('No bookings found') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
                {{ $bookings->appends(request()->query())->links() }}
            </div>
        </div>
    </div>
@endsection
