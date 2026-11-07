@php
    $profil = \App\Models\ProfilSekolah::current();
    $logoUrl = $profil->logoUrl();
    $isPro = \App\Support\LisensiPaket::isPro();
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

    @if ($isPro)
        <span class="inline-flex shrink-0 items-center rounded-md bg-primary-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white shadow-xs dark:bg-primary-500">
            PRO
        </span>
    @endif
</span>
