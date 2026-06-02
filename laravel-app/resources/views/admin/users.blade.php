@extends('layouts.admin')

@section('content')
	@php
		$visibleHistoryCount = $users->getCollection()->sum('histories_count');
	@endphp

	<div class="admin-page-stack">
		<div class="admin-page-head">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
				<div class="d-flex gap-3 align-items-start">
					<span class="admin-page-icon"><i class="fa-solid fa-users-gear"></i></span>
					<div>
						<p class="eyebrow mb-2">Kelola User</p>
						<h1 class="fw-bold mb-2">Atur akses pengguna</h1>
						<p class="section-subtitle mb-0">Pantau akun, ubah role, dan jaga akses admin tetap terkendali.</p>
					</div>
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

		<div class="row g-3">
			<div class="col-md-4">
				<div class="admin-stat">
					<div class="d-flex justify-content-between align-items-start gap-3">
						<div>
							<span>User ditemukan</span>
							<strong>{{ $users->total() }}</strong>
						</div>
						<i class="fa-solid fa-users stat-icon"></i>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="admin-stat">
					<div class="d-flex justify-content-between align-items-start gap-3">
						<div>
							<span>Admin aktif</span>
							<strong>{{ $adminCount }}</strong>
						</div>
						<i class="fa-solid fa-shield-halved stat-icon"></i>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="admin-stat">
					<div class="d-flex justify-content-between align-items-start gap-3">
						<div>
							<span>History halaman ini</span>
							<strong>{{ $visibleHistoryCount }}</strong>
						</div>
						<i class="fa-solid fa-clock-rotate-left stat-icon"></i>
					</div>
				</div>
			</div>
		</div>

		<div class="admin-panel">
			<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
				<div>
					<h2 class="h5 fw-bold mb-1">Direktori pengguna</h2>
					<p class="section-subtitle mb-0">Gunakan filter untuk menemukan akun dan ubah role langsung dari tabel.</p>
				</div>
			</div>

			<form class="filter-card row g-3 align-items-end mb-4" method="GET" action="{{ route('admin.users') }}">
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
				<table class="table admin-table responsive-table align-middle">
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
								<td data-label="ID" class="fw-bold">#{{ $user->id }}</td>
								<td data-label="Nama">
									<div class="entity-cell">
										<span class="entity-avatar {{ $user->role === 'admin' ? 'admin' : '' }}">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</span>
										<div>
											<div class="table-title">{{ $user->name }}</div>
											@if($user->is(auth()->user()))
												<div class="muted-line">Akun kamu</div>
											@endif
										</div>
									</div>
								</td>
								<td data-label="Email">{{ $user->email }}</td>
								<td data-label="Role">
									<span class="role-badge {{ $user->role === 'admin' ? 'admin' : '' }}">
										<i class="fa-solid {{ $user->role === 'admin' ? 'fa-shield-halved' : 'fa-user' }}"></i>
										{{ ucfirst($user->role ?? 'user') }}
									</span>
								</td>
								<td data-label="Tanggal">{{ optional($user->created_at)->format('d M Y H:i') }}</td>
								<td data-label="History"><span class="metric-pill">{{ $user->histories_count }} history</span></td>
								<td data-label="Aksi">
									<div class="action-stack">
										<form action="{{ route('admin.users.update', $user) }}" method="POST" class="d-flex flex-wrap gap-2 justify-content-end">
											@csrf
											@method('PATCH')
											<select class="form-select form-select-sm" name="role" aria-label="Ubah role {{ $user->name }}">
												<option value="user" @selected($user->role === 'user')>User</option>
												<option value="admin" @selected($user->role === 'admin')>Admin</option>
											</select>
											<button class="btn btn-sm btn-outline-dark" type="submit">
												<i class="fa-solid fa-floppy-disk me-1"></i>Simpan
											</button>
										</form>
										<form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('Yakin hapus user ini? History dan datasetnya akan disimpan sebagai data sistem.')" class="m-0">
											@csrf
											@method('DELETE')
											<button class="btn btn-sm btn-outline-danger" type="submit" @disabled($user->is(auth()->user()))>
												<i class="fa-solid fa-trash-can me-1"></i>Hapus
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

			<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
				<p class="text-muted mb-0">Total admin saat ini: <strong>{{ $adminCount }}</strong></p>
				{{ $users->links() }}
			</div>
		</div>
	</div>
@endsection
