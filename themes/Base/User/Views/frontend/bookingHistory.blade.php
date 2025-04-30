@extends('layouts.user')

@section('content')
    <h2 class="title-bar no-border-bottom">
        {{ __('Booking History') }}
    </h2>

    @include('admin.message')

    <div class="booking-history-manager">
        <div class="tabbable">
            <ul class="nav nav-tabs ht-nav-tabs">
                @php $status_type = request()->query('status'); @endphp
                <li class="{{ empty($status_type) ? 'active' : '' }}">
                    <a href="{{ route('user.booking_history') }}">{{ __('All Bookings') }}</a>
                </li>
                @if (!empty($statues))
                    {{-- Typo preserved if your controller sends "statues" --}}
                    @foreach ($statues as $status)
                        <li class="{{ !empty($status_type) && $status_type == $status ? 'active' : '' }}">
                            <a href="{{ route('user.booking_history', ['status' => $status]) }}">
                                {{ booking_status_to_text($status) }}
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>

            @if (!empty($bookings) && $bookings->count() > 0)
                <div class="tab-content mt-4">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-booking-history">
                            <thead>
                                <tr>
                                    <th>{{ __('Order ID') }}</th>
                                    <th>{{ __('Booked By') }}</th>
                                    <th>{{ __('Order Date') }}</th>
                                    <th>{{ __('Payment Amount') }}</th>
                                    <th>{{ __('Currency') }}</th>
                                    <th>{{ __('Status (Live)') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($bookings as $booking)
                                    <tr>
                                        <td>{{ $booking->order_id }}</td>
                                        <td>{{ ucfirst($booking->booked_by) }}</td>
                                        <td>{{ $booking->created_at->format('d/m/Y') }}</td>
                                        <td>{{ number_format($booking->payment_amount, 2) }}</td>
                                        <td>{{ $booking->currency_code }}</td>
                                        <td>
                                            {{ $statuses[$booking->order_id] ?? ($booking->pcb_status ?? 'N/A') }}
                                        </td>
                                        <td>
                                            <a href="{{ route('hotel.booking.invoice', ['orderId' => $booking->order_id]) }}"
                                                target="_blank" class="btn btn-sm btn-info">
                                                {{ __('Invoice') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="bravo-pagination">
                        {{ $bookings->appends(request()->query())->links() }}
                    </div>
                </div>
            @else
                <div class="text-center mt-4">{{ __('No Booking History Found') }}</div>
            @endif
        </div>
    </div>
@endsection
