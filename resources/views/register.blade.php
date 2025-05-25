@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <h2 class="mb-4 text-center fw-bold">Sign up</h2>
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                        name="name" value="{{ old('name') }}" required autofocus>
                    @error('name')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                        name="email" value="{{ old('email') }}" required>
                    @error('email')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Create a password</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                        name="password" required>
                    @error('password')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm password</label>
                    <input id="password_confirmation" type="password" class="form-control"
                        name="password_confirmation" required>
                </div>

                <!-- Checkbox -->
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input @error('terms') is-invalid @enderror"
                        id="terms" name="terms" {{ old('terms') ? 'checked' : '' }} required>
                    <label class="form-check-label" for="terms">
                        I've read and agree with the <a href="#">Terms and Conditions</a> and the <a href="#">Privacy Policy</a>.
                    </label>
                    @error('terms')
                        <span class="invalid-feedback d-block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        Sign Up
                    </button>
                </div>
            </form>

            <!-- Bottom Image Section (as background) -->
            <div class="mt-4 position-relative" style="height: 200px;">
                <img src="{{ asset('assets/images/rec1.png') }}" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover; z-index: 1;">
                <img src="{{ asset('assets/images/rec2.png') }}" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover; z-index: 2;">
                <div class="position-absolute bottom-0 start-0 end-0 text-center z-3 mb-3">
                    <span>Already have an account?</span>
                    <a href="{{ route('login') }}" class="btn btn-link text-primary">Log in</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
