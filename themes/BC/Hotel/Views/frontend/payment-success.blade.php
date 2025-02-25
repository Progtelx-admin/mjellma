@extends('layouts.app')

@section('content')
    <div class="container text-center mt-5 mb-5">
        <div class="card shadow-sm p-5">
            <h1 class="fw-bold text-success">Payment Successful!</h1>
            <p class="mt-3">Your booking has been successfully completed.
            </p>
            <div class="mt-4">
                <a href="{{ route('hotel.show') }}" class="btn btn-primary">Back to Search</a>
            </div>
        </div>
    </div>
@endsection
