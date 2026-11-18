@props(['user', 'size' => 'sm'])
<span {{ $attributes->class(['avatar', 'avatar-'.$size]) }}>
    @if ($user->fotoUrl())
        <img src="{{ $user->fotoUrl() }}" alt="Foto {{ $user->name }}">
    @else
        {{ $user->inisial() }}
    @endif
</span>
