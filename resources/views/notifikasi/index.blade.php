@extends('layouts.app')
@section('title', 'Notifikasi')

@section('content')
    <div class="page-head">
        <div>
            <h1>Notifikasi</h1>
            <p>Pembaruan terbaru tentang pengaduan Anda.</p>
        </div>
        @if (auth()->user()->notifikasi()->belumDibaca()->exists())
            <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                @csrf
                <button class="btn btn-outline" type="submit">Tandai semua dibaca</button>
            </form>
        @endif
    </div>

    <div class="card">
        @if ($daftar->isEmpty())
            <x-empty>Belum ada notifikasi.</x-empty>
        @else
            <ul class="notif-list">
                @foreach ($daftar as $n)
                    <li>
                        <a class="notif-row @if (! $n->sudahDibaca()) unread @endif" href="{{ route('notifikasi.buka', $n->id) }}">
                            <span class="notif-dot" aria-hidden="true"></span>
                            <span class="notif-text">
                                <strong>{{ $n->judul }}</strong>
                                @if ($n->pesan)<span class="muted">{{ $n->pesan }}</span>@endif
                            </span>
                            <span class="notif-time small muted">{{ $n->created_at->locale('id')->diffForHumans() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            {{ $daftar->links() }}
        @endif
    </div>
@endsection
