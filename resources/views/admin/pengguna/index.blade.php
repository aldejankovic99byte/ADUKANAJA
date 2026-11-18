@extends('layouts.app')
@section('title', 'Data Pengguna')

@section('content')
    <div class="page-head">
        <h1>Kelola Data Pengguna</h1>
        <a class="btn btn-dark" href="{{ route('admin.pengguna.create') }}">+ Tambah Pengguna</a>
    </div>

    <form class="filters" method="GET" action="{{ route('admin.pengguna.index') }}" style="margin-bottom:14px">
        <div class="field" style="min-width:260px">
            <label class="sr-only" for="q" style="position:absolute;left:-9999px">Cari pengguna</label>
            <input id="q" name="q" type="search" value="{{ $q }}" placeholder="Cari nama, email, NIS, atau kelas…">
        </div>
        <div class="field">
            <label class="sr-only" for="role" style="position:absolute;left:-9999px">Filter role</label>
            <select id="role" name="role" onchange="this.form.submit()">
                <option value="">Semua role</option>
                @foreach (\App\Models\User::roleLabels() as $v => $l)
                    <option value="{{ $v }}" @selected($role === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-outline" type="submit">Cari</button>
        @if ($q !== '' || $role) <a class="btn btn-outline" href="{{ route('admin.pengguna.index') }}">Reset</a> @endif
    </form>

    <div class="card">
        @if ($users->isEmpty())
            <x-empty>Tidak ada pengguna yang cocok.</x-empty>
        @else
            <div class="table-wrap">
                <table class="stack">
                    <thead><tr><th>Nama</th><th>Role</th><th>Email</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td data-label="Nama">
                                <div class="who">
                                    <x-avatar :user="$u" size="md" />
                                    <div style="text-align:left">
                                        <strong>{{ $u->name }}</strong>
                                        @if ($u->nis)<div class="small muted">{{ $u->nis }} · {{ $u->kelas }}</div>@endif
                                    </div>
                                </div>
                            </td>
                            <td data-label="Role">{{ $u->roleLabel() }}</td>
                            <td data-label="Email">{{ $u->email }}</td>
                            <td data-label="Aksi">
                                <div class="actions">
                                    <a href="{{ route('admin.pengguna.edit', $u) }}">Edit</a>
                                    @if ($u->id !== auth()->id())
                                        <form method="POST" action="{{ route('admin.pengguna.destroy', $u) }}" onsubmit="return confirm('Hapus pengguna {{ e($u->name) }}?')">
                                            @csrf @method('DELETE')
                                            <button class="link-danger" type="submit">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $users->links() }}
        @endif
    </div>
@endsection
