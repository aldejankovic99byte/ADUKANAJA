@extends('layouts.app')
@section('title', 'Ajukan Pengaduan')

@section('content')
    <div class="aduan-hero">
        <div>
            <div class="aduan-hero-badge">
                <x-icon name="tambah" style="width:14px;height:14px" />
                <span>Formulir Pengaduan</span>
            </div>
            <h1>Ajukan Pengaduan Kerusakan Fasilitas</h1>
            <p>Laporkan sarana atau fasilitas sekolah yang mengalami kerusakan agar segera diverifikasi dan ditindaklanjuti oleh petugas teknisi.</p>
        </div>
        <div class="aduan-hero-badge-right">
            <x-icon name="cek" style="width:16px;height:16px;color:#8FB1F0" />
            <span>Respon Cepat &amp; Terpantau</span>
        </div>
    </div>

    <form id="form-aduan" class="aduan-card" method="POST" action="{{ route('pengaduan.store') }}" enctype="multipart/form-data" novalidate>
        @csrf

        <div class="aduan-info-tip">
            <x-icon name="info" class="ico" />
            <div>
                <strong>Petunjuk Pengisian:</strong> Pastikan informasi kategori, lokasi ruangan, dan deskripsi kerusakan diisi secara rinci. Sertakan juga foto bukti kerusakan agar mempermudah teknisi dalam mempersiapkan peralatan perbaikan.
            </div>
        </div>

        <!-- Bagian 1: Data Kerusakan & Lokasi -->
        <div class="form-section-head">
            <span class="section-step-num">1</span>
            <span class="section-step-title">Informasi Fasilitas &amp; Lokasi</span>
        </div>

        <div class="form-row">
            <div class="field @error('kategori_id') has-error @enderror">
                <label for="kategori_id">
                    Kategori Kerusakan <span class="req-star" title="Wajib diisi">*</span>
                </label>
                <select id="kategori_id" name="kategori_id" required>
                    <option value="">— Pilih kategori kerusakan —</option>
                    @foreach ($kategori as $k)
                        <option value="{{ $k->id }}" @selected(old('kategori_id') == $k->id)>{{ $k->nama }}</option>
                    @endforeach
                </select>
                @error('kategori_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field @error('lokasi') has-error @enderror">
                <label for="lokasi">
                    Lokasi / Ruangan <span class="req-star" title="Wajib diisi">*</span>
                </label>
                <input id="lokasi" name="lokasi" type="text" list="daftar-lokasi" value="{{ old('lokasi') }}" placeholder="Contoh: Lab Komputer 1 / Ruang Kelas XII RPL B" required>
                <datalist id="daftar-lokasi">
                    @foreach ($lokasiSaran as $l) <option value="{{ $l }}"> @endforeach
                </datalist>
                @error('lokasi') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="form-row">
            <div class="field @error('tanggal_kejadian') has-error @enderror">
                <label for="tanggal_kejadian">
                    Tanggal Kejadian <span class="req-star" title="Wajib diisi">*</span>
                </label>
                <input id="tanggal_kejadian" name="tanggal_kejadian" type="date" max="{{ now()->toDateString() }}" value="{{ old('tanggal_kejadian', now()->toDateString()) }}" required>
                @error('tanggal_kejadian') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="field @error('sarana_id') has-error @enderror">
                <label for="sarana_id">
                    Sarana Spesifik <span class="badge-optional">Opsional</span>
                </label>
                <select id="sarana_id" name="sarana_id">
                    <option value="">— Tidak ada / tidak tahu —</option>
                    @foreach ($sarana as $s)
                        <option value="{{ $s->id }}" @selected(old('sarana_id') == $s->id)>{{ $s->label() }}</option>
                    @endforeach
                </select>
                @error('sarana_id') <p class="field-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <!-- Bagian 2: Deskripsi & Bukti Foto -->
        <div class="form-section-head">
            <span class="section-step-num">2</span>
            <span class="section-step-title">Rincian Kerusakan &amp; Bukti Foto</span>
        </div>

        <div class="field @error('deskripsi') has-error @enderror">
            <label for="deskripsi">
                Deskripsi Kerusakan <span class="req-star" title="Wajib diisi">*</span>
            </label>
            <textarea id="deskripsi" name="deskripsi" placeholder="Jelaskan secara detail bagian yang rusak, penyebab (jika diketahui), atau tingkat keparahan kerusakan..." required>{{ old('deskripsi') }}</textarea>
            @error('deskripsi') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="field @error('foto') has-error @enderror">
            <span class="label">
                Upload Foto Bukti Kerusakan <span class="badge-optional">Opsional</span>
            </span>
            <label class="aduan-dropzone" id="dropzone" for="foto">
                <div class="dz-icon-wrap" id="dz-icon-wrap">
                    <x-icon name="inbox" class="ico" />
                </div>
                <div class="dz-main-text" id="dz-text">
                    <span>Klik untuk memilih file foto</span> atau seret foto ke sini
                </div>
                <div class="dz-sub-text" id="dz-sub">Format PNG atau JPG (maksimal 2 MB)</div>

                <div class="dz-file-badge" id="dz-file-badge" hidden>
                    <x-icon name="cek" class="ico" />
                    <span id="dz-filename"></span>
                </div>

                <img id="dz-preview" alt="Pratinjau foto bukti" class="dz-preview-img" hidden>
                <input id="foto" name="foto" type="file" accept="image/png,image/jpeg">
            </label>
            @error('foto') <p class="field-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-actions">
            <a class="btn btn-outline" href="{{ route('beranda') }}">Batal</a>
            <button id="btn-kirim" class="btn-kirim-aduan" type="submit">
                <x-icon name="tambah" class="ico" style="width:18px;height:18px" />
                <span>Kirim Pengaduan</span>
            </button>
        </div>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.getElementById('form-aduan');
        var btn = document.getElementById('btn-kirim');
        var required = form.querySelectorAll('[required]');

        // Tombol "Kirim" nonaktif sampai kolom wajib terisi
        function cek() {
            var ok = true;
            required.forEach(function (el) { if (!el.value.trim()) ok = false; });
            btn.disabled = !ok;
        }
        form.addEventListener('input', cek);
        form.addEventListener('change', cek);
        cek();

        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.innerHTML = '<span>Mengirim…</span>';
        });

        // Interaksi Dropzone Foto
        var dropzone = document.getElementById('dropzone');
        var input = document.getElementById('foto');
        var img = document.getElementById('dz-preview');
        var txt = document.getElementById('dz-text');
        var sub = document.getElementById('dz-sub');
        var fileBadge = document.getElementById('dz-file-badge');
        var fileName = document.getElementById('dz-filename');

        function handleFile(file) {
            if (!file) {
                img.hidden = true;
                fileBadge.hidden = true;
                txt.hidden = false;
                sub.hidden = false;
                return;
            }
            img.src = URL.createObjectURL(file);
            img.hidden = false;
            fileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
            fileBadge.hidden = false;
            txt.innerHTML = '<span>Ganti foto terpilih</span>';
            sub.textContent = 'Klik untuk mengganti dengan file lain';
        }

        input.addEventListener('change', function () {
            if (input.files && input.files[0]) {
                handleFile(input.files[0]);
            }
        });

        // Drag & Drop visual feedback
        ['dragenter', 'dragover'].forEach(function (evt) {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.add('drag');
            });
        });

        ['dragleave', 'drop'].forEach(function (evt) {
            dropzone.addEventListener(evt, function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('drag');
            });
        });

        dropzone.addEventListener('drop', function (e) {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0]) {
                input.files = e.dataTransfer.files;
                handleFile(e.dataTransfer.files[0]);
            }
        });
    })();
</script>
@endpush