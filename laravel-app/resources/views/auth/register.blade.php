@extends('layouts.app')

@section('content')
    <style>
        .auth-shell {
            max-width: 500px;
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
            background: #dcfce7;
            color: #15803d;
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
                    <i class="fa-solid fa-user-plus"></i>
                </div>
                <p class="eyebrow mb-2">Register</p>
                <h1 class="h4 fw-bold mb-2">Buat akun baru</h1>
                <p class="text-muted mb-0">Akun dipakai untuk menyimpan riwayat prediksi masing-masing user.</p>
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

            <form action="{{ route('register.store') }}" method="POST" class="d-grid gap-3">
                @csrf

                <div>
                    <label class="form-label" for="name">Nama</label>
                    <input class="form-control" id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Nama lengkap" required autofocus>
                </div>

                <div>
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required>
                </div>

                <div>
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" type="password" name="password" placeholder="Minimal 8 karakter" required>
                </div>

                <div>
                    <label class="form-label" for="password_confirmation">Konfirmasi Password</label>
                    <input class="form-control" id="password_confirmation" type="password" name="password_confirmation" placeholder="Ulangi password" required>
                </div>

                <button class="btn btn-dark btn-lg" type="submit">Register</button>
            </form>

            <p class="text-center text-muted mt-4 mb-0">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Login</a>
            </p>
        </div>
    </div>
@endsection
