@extends('layouts.echo')

@section('title', 'Dashboard · Echo-Map')

@section('content')
    <h1>Hi, {{ auth()->user()->name }}</h1>
    <p class="sub">Welcome back. Pick a place to start.</p>

    <div class="tile-row">
        <a class="tile" href="{{ url('/map') }}">
            <h3>Open the map</h3>
            <p>Add a memory, browse pins, and read AI location stories.</p>
        </a>
        @unless (auth()->user()->is_admin)
            <a class="tile" href="{{ url('/friends') }}">
                <h3>Friends</h3>
                <p>Send or accept requests. Control who sees your friends-only pins.</p>
            </a>
        @endunless
        @if (auth()->user()->is_admin)
            <a class="tile" href="{{ url('/admin/reports') }}">
                <h3>Admin reports</h3>
                <p>Review reported content and hide anything inappropriate.</p>
            </a>
        @endif
    </div>
@endsection