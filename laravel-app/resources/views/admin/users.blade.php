@extends('layouts.admin')

@section('content')
	<style>
		.admin-page-head {
			border-radius: 20px;
			border: 1px solid rgba(22, 163, 74, 0.18);
			background:
				radial-gradient(circle at top right, rgba(34, 197, 94, 0.14), transparent 30%),
				linear-gradient(135deg, rgba(236, 253, 245, 0.96), rgba(255, 255, 255, 0.98));
			padding: 1.4rem;
			margin-bottom: 1.5rem;
		}

		.admin-panel {
			border: 1px solid var(--admin-line);
			border-radius: 18px;
			background: #fff;
			padding: 1.25rem;
			box-shadow: var(--shadow-sm);
		}

		.role-badge {
			display: inline-flex;
			align-items: center;
			gap: 0.35rem;
			border-radius: 999px;
			padding: 0.4rem 0.65rem;
			font-size: 0.78rem;
			font-weight: 800;
			background: #eef7f1;
			color: #475569;
		}

		.role-badge.admin {
			background: var(--admin-brand-soft);
			color: var(--admin-brand-deep);
		}

		.table td,
		.table th {
			vertical-align: middle;
		}

		.action-stack {
			display: flex;
			flex-wrap: wrap;
			gap: 0.5rem;
			justify-content: flex-end;
		}
	</style>

	<div class="admin-page-head">
		<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
			<div>
				<p class="eyebrow mb-2">Kelola User</p>
				<h1 class="fw-bold mb-2">Atur akses pengguna.</h1>
				<p class="section-subtitle mb-0">Admin bisa melihat user, mengubah role, dan menghapus akun dengan pengamanan dasar.</p>
			</div>
			<a href="{{ route('admin.dashboard') }}" class="btn btn-outline-dark">
				<i class="fa-solid fa-arrow-left me-2"></i>Dashboard
			</a>
		</div>
	</div>

	@if(session('success'))
		<div class="alert alert-success">{{ session('success') }}</div>
	@endif

	@if($errors->any())
		<div class="alert alert-danger">
			<ul class="mb-0">
				@foreach($errors->all() as $error)
					<li>{{ $error }}</li>
				@endforeach
			</ul>
		</div>
	@endif

	<div class="admin-panel">
		<form class="row g-3 align-items-end mb-4" method="GET" action="{{ route('admin.users') }}">
			<div class="col-md-6">
				<label class="form-label fw-bold" for="search">Cari user</label>
				<input id="search" class="form-control" type="search" name="search" value="{{ $filters['search'] }}" placeholder="Nama atau email">
			</div>
			<div class="col-md-3">
				<label class="form-label fw-bold" for="role">Filter role</label>
				<select id="role" class="form-select" name="role">
					<option value="">Semua role</option>
					<option value="admin" @selected($filters['role'] === 'admin')>Admin</option>
					<option value="user" @selected($filters['role'] === 'user')>User</option>
				</select>
			</div>
			<div class="col-md-3 d-grid">
				<button class="btn btn-dark" type="submit">
					<i class="fa-solid fa-magnifying-glass me-2"></i>Terapkan
				</button>
			</div>
		</form>

		<div class="table-responsive">
			<table class="table align-middle">
				<thead>
					<tr>
						<th>ID</th>
						<th>Nama</th>
						<th>Email</th>
						<th>Role</th>
						<th>Tanggal Daftar</th>
						<th>History</th>
						<th class="text-end">Aksi</th>
					</tr>
				</thead>
				<tbody>
					@forelse($users as $user)
						<tr>
							<td class="fw-bold">#{{ $user->id }}</td>
							<td>
								<div class="fw-bold">{{ $user->name }}</div>
								@if($user->is(auth()->user()))
									<div class="text-muted small">Akun kamu</div>
								@endif
							</td>
							<td>{{ $user->email }}</td>
							<td>
								<span class="role-badge {{ $user->role === 'admin' ? 'admin' : '' }}">
									<i class="fa-solid {{ $user->role === 'admin' ? 'fa-shield-halved' : 'fa-user' }}"></i>
									{{ ucfirst($user->role ?? 'user') }}
								</span>
							</td>
							<td>{{ optional($user->created_at)->format('d M Y H:i') }}</td>
							<td>{{ $user->histories_count }}</td>
							<td>
								<div class="action-stack">
									<form action="{{ route('admin.users.update', $user) }}" method="POST" class="d-flex gap-2">
										@csrf
										@method('PATCH')
										<select class="form-select form-select-sm" name="role" aria-label="Ubah role {{ $user->name }}">
											<option value="user" @selected($user->role === 'user')>User</option>
											<option value="admin" @selected($user->role === 'admin')>Admin</option>
										</select>
										<button class="btn btn-sm btn-outline-dark" type="submit">Simpan</button>
									</form>
									<form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Yakin hapus user ini? History dan datasetnya akan disimpan sebagai data sistem.')" class="m-0">
										@csrf
										@method('DELETE')
										<button class="btn btn-sm btn-outline-danger" type="submit" @disabled($user->is(auth()->user()))>
											Hapus
										</button>
									</form>
								</div>
							</td>
						</tr>
					@empty
						<tr>
							<td colspan="7" class="text-center text-muted py-4">Tidak ada user yang cocok.</td>
						</tr>
					@endforelse
				</tbody>
			</table>
		</div>

		<div class="d-flex justify-content-between align-items-center mt-3">
			<p class="text-muted mb-0">Total admin saat ini: <strong>{{ $adminCount }}</strong></p>
			{{ $users->links() }}
		</div>
	</div>
@endsection
