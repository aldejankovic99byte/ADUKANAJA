@props(['status'])
@php $label = \App\Models\Pengaduan::penangananLabels()[$status] ?? ucfirst((string) $status); @endphp
<span class="badge badge-p-{{ $status }}">{{ $label }}</span>
