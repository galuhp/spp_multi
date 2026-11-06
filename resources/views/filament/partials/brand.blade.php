@php
    $profil = \App\Models\ProfilSekolah::current();
    $logoUrl = $profil->logoUrl();
@endphp

<span class="flex min-w-0 items-center gap-2.5">
    @if ($logoUrl)
        <img
            src="{{ $logoUrl }}"
            alt="Logo {{ $profil->nama_sekolah }}"
            class="h-9 w-9 shrink-0 rounded-lg object-contain"
        >
    @endif

    <span class="truncate">{{ $profil->appName() }}</span>
</span>
