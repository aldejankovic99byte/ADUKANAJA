@props(['riwayat', 'tampilCatatan' => true])
@if ($riwayat->isEmpty())
    <p class="muted small">Belum ada riwayat.</p>
@else
    <ol class="timeline">
        @foreach ($riwayat as $r)
            <li class="t-{{ $r->jenis }}">
                <div class="t-judul">{{ $r->judul() }}</div>
                <div class="t-meta">{{ $r->created_at?->translatedFormat('d M Y, H:i') }}@if ($r->user) · {{ $r->user->name }}@endif</div>
                @if ($tampilCatatan && $r->catatan)
                    <div class="t-catatan-teks">{{ $r->catatan }}</div>
                @endif
            </li>
        @endforeach
    </ol>
@endif
