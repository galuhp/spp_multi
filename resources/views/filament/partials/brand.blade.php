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
    @else
        <span
            style="width: 2.25rem; height: 2.25rem; min-width: 2.25rem; border-radius: 0.5rem; background-color: var(--primary-600, #2563eb); color: #ffffff; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;"
        >
            <x-heroicon-o-academic-cap
                width="20"
                height="20"
                style="width: 1.25rem; height: 1.25rem; flex-shrink: 0;"
            />
        </span>
    @endif

    <span class="truncate">{{ $profil->appName() }}</span>

    @if ($isPro)
        <span class="inline-flex shrink-0 items-center rounded-md bg-primary-600 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white shadow-xs dark:bg-primary-500">
            PRO
        </span>
    @endif
</span>
