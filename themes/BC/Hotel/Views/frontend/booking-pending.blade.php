@extends('layouts.app')

@section('content')
    <div class="container mx-auto p-6">
        <h1 class="text-2xl font-semibold mb-4">Your booking is being processed</h1>
        <p>Status: <strong>{{ $status['status'] }}</strong></p>

        {{-- Optional: auto-refresh every 10 seconds --}}
        <script>
            setTimeout(function() {
                window.location.reload();
            }, 10000);
        </script>
    </div>
@endsection
