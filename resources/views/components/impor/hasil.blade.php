@props([
    'judul',
    'hasil',
    'properti',
])

@php
    $berhasil = (int) ($hasil['berhasil'] ?? 0);
    $baru = (int) ($hasil['baru'] ?? 0);
    $diperbarui = (int) ($hasil['diperbarui'] ?? 0);
    $gagal = (int) ($hasil['gagal'] ?? 0);
    $errors = $hasil['errors'] ?? [];
    $total = $berhasil + $gagal;
    $sukses = $berhasil > 0;
    $persen = $total > 0 ? (int) round(($berhasil / $total) * 100) : 0;

    $statistik = [
        [
            'label' => 'Berhasil',
            'nilai' => $berhasil,
            'kelas' => 'border-slate-200/80 bg-slate-50/60 dark:border-white/10 dark:bg-white/[0.03]',
            'titik' => 'bg-slate-400',
            'labelKelas' => 'text-slate-500 dark:text-gray-400',
            'nilaiKelas' => 'text-slate-900 dark:text-white',
        ],
        [
            'label' => 'Baru',
            'nilai' => $baru,
            'kelas' => 'border-emerald-200/80 bg-emerald-50/60 dark:border-emerald-500/20 dark:bg-emerald-500/10',
            'titik' => 'bg-emerald-500',
            'labelKelas' => 'text-emerald-700 dark:text-emerald-400',
            'nilaiKelas' => 'text-emerald-700 dark:text-emerald-400',
        ],
        [
            'label' => 'Diperbarui',
            'nilai' => $diperbarui,
            'kelas' => 'border-sky-200/80 bg-sky-50/60 dark:border-sky-500/20 dark:bg-sky-500/10',
            'titik' => 'bg-sky-500',
            'labelKelas' => 'text-sky-700 dark:text-sky-400',
            'nilaiKelas' => 'text-sky-700 dark:text-sky-400',
        ],
        [
            'label' => 'Gagal / dilewati',
            'nilai' => $gagal,
            'kelas' => 'border-rose-200/80 bg-rose-50/60 dark:border-rose-500/20 dark:bg-rose-500/10',
            'titik' => 'bg-rose-500',
            'labelKelas' => 'text-rose-700 dark:text-rose-400',
            'nilaiKelas' => 'text-rose-700 dark:text-rose-400',
        ],
    ];
@endphp

<section
    {{ $attributes->class('fi-section scroll-mt-24 overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs dark:border-white/10 dark:bg-gray-900') }}
    x-data
    x-init="$nextTick(() => {
        const kotak = $el.getBoundingClientRect();
        const sudahTerlihat = kotak.top >= 0 && kotak.bottom <= window.innerHeight;

        if (sudahTerlihat) {
            return;
        }

        $el.scrollIntoView({
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth',
            block: 'start',
        });
    })"
    aria-label="{{ $judul }}"
>
    <div class="border-b border-slate-200/80 p-5 dark:border-white/10">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $sukses ? 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200/70 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20' : 'bg-rose-50 text-rose-600 ring-1 ring-rose-200/70 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20' }}">
                    @if ($sukses)
                        <x-heroicon-o-check-circle class="size-5" aria-hidden="true" />
                    @else
                        <x-heroicon-o-exclamation-triangle class="size-5" aria-hidden="true" />
                    @endif
                </span>

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ $judul }}</h3>

                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $sukses ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/70 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-500/20' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-200/70 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20' }}">
                            {{ $sukses ? 'Berhasil' : 'Perlu diperiksa' }}
                        </span>
                    </div>

                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-gray-400" aria-live="polite" aria-atomic="true">
                        @if ($sukses)
                            {{ $berhasil }} dari {{ $total }} baris berhasil diproses.
                        @else
                            Tidak ada baris yang dapat diproses. Periksa format dan isi berkas, lalu coba lagi.
                        @endif
                    </p>
                </div>
            </div>

            <button
                type="button"
                wire:click="$set('{{ $properti }}', null)"
                class="inline-flex size-10 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200"
                title="Tutup ringkasan"
                aria-label="Tutup ringkasan hasil impor"
            >
                <x-heroicon-o-x-mark class="size-4" aria-hidden="true" />
            </button>
        </div>

        @if ($total > 0)
            <div class="mt-4">
                <div class="flex items-center justify-between gap-2 text-[11px] font-medium text-slate-500 dark:text-gray-400">
                    <span>Tingkat keberhasilan</span>
                    <span class="font-semibold text-slate-700 tabular-nums dark:text-gray-200">{{ $persen }}%</span>
                </div>

                <div
                    class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-white/10"
                    role="progressbar"
                    aria-label="Persentase baris berhasil diproses"
                    aria-valuenow="{{ $persen }}"
                    aria-valuemin="0"
                    aria-valuemax="100"
                >
                    <div
                        class="h-full rounded-full transition-[width] duration-300 motion-reduce:transition-none {{ $sukses ? 'bg-emerald-500' : 'bg-rose-500' }}"
                        style="width: {{ $persen }}%"
                    ></div>
                </div>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-2.5 p-5 sm:grid-cols-4 sm:gap-3">
        @foreach ($statistik as $stat)
            <div class="rounded-lg border p-3.5 {{ $stat['kelas'] }}">
                <div class="flex items-center gap-2">
                    <span class="size-1.5 shrink-0 rounded-full {{ $stat['titik'] }}" aria-hidden="true"></span>
                    <p class="text-[11px] font-medium tracking-wide uppercase {{ $stat['labelKelas'] }}">{{ $stat['label'] }}</p>
                </div>

                <p class="mt-1.5 text-xl font-bold tabular-nums sm:text-2xl {{ $stat['nilaiKelas'] }}">{{ $stat['nilai'] }}</p>
            </div>
        @endforeach
    </div>

    @if ($gagal > 0 && $errors !== [])
        <div class="px-5 pb-5">
            <details class="group overflow-hidden rounded-lg border border-rose-200/80 bg-rose-50/50 dark:border-rose-500/20 dark:bg-rose-500/5" {{ ! $sukses ? 'open' : '' }}>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-500 [&::-webkit-details-marker]:hidden">
                    <span class="flex items-center gap-2 text-xs font-semibold text-rose-700 dark:text-rose-400">
                        <x-heroicon-o-exclamation-triangle class="size-4 shrink-0" aria-hidden="true" />
                        {{ $gagal }} baris gagal / dilewati
                    </span>

                    <span class="flex items-center gap-1 text-[11px] font-medium text-rose-600 dark:text-rose-400">
                        Lihat rincian
                        <x-heroicon-o-chevron-down class="size-4 transition-transform duration-200 group-open:rotate-180 motion-reduce:transition-none" aria-hidden="true" />
                    </span>
                </summary>

                <ul class="impor-error-scroll max-h-56 space-y-1.5 overflow-y-auto border-t border-rose-200/70 px-4 py-3 text-xs leading-relaxed text-rose-700 dark:border-rose-500/20 dark:text-rose-300">
                    @foreach ($errors as $error)
                        <li class="flex gap-2">
                            <span class="mt-1.5 size-1 shrink-0 rounded-full bg-rose-400" aria-hidden="true"></span>
                            <span>{{ $error }}</span>
                        </li>
                    @endforeach
                </ul>
            </details>
        </div>
    @endif
</section>
