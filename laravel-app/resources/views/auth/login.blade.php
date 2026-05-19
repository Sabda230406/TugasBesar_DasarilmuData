@extends('layouts.app')

@section('content')
    <style>
        .auth-shell {
            max-width: 460px;
            margin: 0 auto;
        }

        .auth-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
            padding: 2rem;
        }

        .auth-icon {
            width: 52px;
            height: 52px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 1.35rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.75rem 0.9rem;
        }
    </style>

    <div class="auth-shell">
        <div class="auth-card">
            <div class="text-center mb-4">
                <div class="auth-icon mb-3">
                    <i class="fa-solid fa-right-to-bracket"></i>
                </div>
                <p class="eyebrow mb-2">Login</p>
                <h1 class="h4 fw-bold mb-2">Masuk ke akun Anda</h1>
                <p class="text-muted mb-0">Gunakan akun yang sudah terdaftar untuk mulai prediksi.</p>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('login.store') }}" method="POST" class="d-grid gap-3">
                @csrf

                <div>
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus>
                </div>

                <div>
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" type="password" name="password" placeholder="Password akun" required>
                </div>

                <div class="form-check">
                    <input class="form-check-input" id="remember" type="checkbox" name="remember" value="1">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>

                <button class="btn btn-dark btn-lg" type="submit">Login</button>
            </form>

            <p class="text-center text-muted mt-4 mb-0">
                Belum punya akun?
                <a href="{{ route('register') }}" class="fw-semibold text-decoration-none">Register</a>
            </p>
        </div>
    </div>
@endsection
