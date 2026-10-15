@extends('layouts.echo')

@section('title', 'Set new password · Echo-Map')

@section('content')
    <h1>Set a new password</h1>
    <p class="sub">Choose a strong password to secure your account.</p>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="password">New password</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn">Reset password</button>

        <p class="meta"><a href="{{ route('login') }}">Back to log in</a></p>
    </form>
@endsection