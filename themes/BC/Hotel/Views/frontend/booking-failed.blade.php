@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <div class="alert alert-danger">
            <h4 class="alert-heading">Booking Failed</h4>
            <p>{{ $errors->first('error') }}</p>
            <hr>
            <a href="{{ url('/hotels') }}" class="btn btn-primary">Search Again</a>
        </div>
    </div>
@endsection
