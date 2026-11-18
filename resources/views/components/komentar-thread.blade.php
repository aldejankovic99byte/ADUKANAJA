@props(['pengaduan'])
<section class="komentar" id="komentar">
    <div class="label">Komentar ({{ $pengaduan->komentar->count() }})</div>

    @forelse ($pengaduan->komentar as $k)
        <div class="komentar-item">
            <span class="avatar avatar-sm">{{ $k->penulis?->inisial() ?? '?' }}</span>
            <div class="komentar-body">
                <div class="komentar-meta">
                    <strong>{{ $k->penulis?->name ?? 'Pengguna dihapus' }}</strong>
                    @if ($k->penulis)<span class="komentar-role">{{ $k->penulis->roleLabel() }}</span>@endif
                    <span class="muted small">· {{ $k->created_at->translatedFormat('d M Y, H:i') }}</span>
                </div>
                <div class="komentar-isi">{{ $k->isi }}</div>
            </div>
        </div>
    @empty
        <p class="muted small" style="margin-bottom:12px">Belum ada komentar. Tulis pertanyaan atau pembaruan di bawah.</p>
    @endforelse

    <form method="POST" action="{{ route('komentar.store', $pengaduan) }}" class="komentar-form" novalidate>
        @csrf
        <div class="field @error('isi') has-error @enderror">
            <label class="sr-only" for="isi-komentar">Tulis komentar</label>
            <textarea id="isi-komentar" name="isi" maxlength="1000" placeholder="Tulis komentar…" required>{{ old('isi') }}</textarea>
            @error('isi') <p class="field-error">{{ $message }}</p> @enderror
        </div>
        <button class="btn btn-primary btn-sm" type="submit">Kirim komentar</button>
    </form>
</section>
