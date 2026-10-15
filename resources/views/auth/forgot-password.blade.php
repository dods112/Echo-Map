@extends('layouts.echo')

@section('title', 'Forgot password · Echo-Map')

@section('content')
    <h1>Reset your password</h1>
    <p class="sub">Enter your email and we'll send you a reset link.</p>

    @if (session('status'))
        <div class="flash ok">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn">Email reset link</button>

        <p class="meta"><a href="{{ route('login') }}">Back to log in</a></p>
    </form>
@endsection