@props(['bukti', 'hapusTugas' => null])
@if ($bukti->isEmpty())
    <p class="muted small">Belum ada foto bukti perbaikan.</p>
@else
    <div class="galeri">
        @foreach ($bukti as $b)
            <figure>
                <a href="{{ $b->fotoUrl() }}" target="_blank" rel="noopener"><img src="{{ $b->fotoUrl() }}" alt="Bukti {{ $b->jenis }} perbaikan" loading="lazy"></a>
                <figcaption><strong>{{ ucfirst($b->jenis) }}</strong>@if ($b->keterangan) · {{ $b->keterangan }}@endif</figcaption>
                @if ($hapusTugas)
                    <form method="POST" action="{{ route('teknisi.tugas.bukti.hapus', [$hapusTugas, $b->id]) }}" onsubmit="return confirm('Hapus foto bukti ini?')">
                        @csrf @method('DELETE')
                        <button class="link-danger small" type="submit">Hapus foto</button>
                    </form>
                @endif
            </figure>
        @endforeach
    </div>
@endif
