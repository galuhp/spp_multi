@props([
    'kolom' => [],
    'catatan' => null,
])

<aside {{ $attributes->class('fi-section h-fit scroll-mt-24 rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900 xl:sticky xl:top-24 xl:self-start') }}>
    <div class="flex items-start justify-between gap-3">
        <div class="flex items-start gap-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-600 ring-1 ring-slate-200/70 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
                <x-heroicon-o-book-open class="size-4" aria-hidden="true" />
            </span>

            <div>
                <h4 class="text-sm font-semibold text-slate-900 dark:text-white">Panduan kolom template</h4>
                <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-gray-400">
                    Gunakan nama kolom berikut pada baris pertama.
                </p>
            </div>
        </div>

        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 tabular-nums ring-1 ring-slate-200/70 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
            {{ count($kolom) }} kolom
        </span>
    </div>

    <dl class="mt-4 space-y-3.5">
        @foreach ($kolom as $item)
            <div>
                <dt class="flex flex-wrap items-center gap-1.5">
                    <code class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200/70 dark:bg-white/5 dark:text-gray-200 dark:ring-white/10">{{ $item['nama'] }}</code>

                    @if ($item['wajib'] ?? false)
                        <span class="rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase text-rose-600 ring-1 ring-rose-200/70 dark:bg-rose-500/10 dark:text-rose-400 dark:ring-rose-500/20">
                            Wajib
                        </span>
                    @else
                        <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium tracking-wide uppercase text-slate-600 ring-1 ring-slate-200/70 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10">
                            Opsional
                        </span>
                    @endif
                </dt>

                <dd class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-gray-400">
                    {{ $item['deskripsi'] }}
                </dd>
            </div>
        @endforeach
    </dl>

    @if ($catatan)
        <div class="mt-4 flex gap-2 rounded-xl border border-amber-200/70 bg-amber-50/70 p-3 dark:border-amber-500/20 dark:bg-amber-500/10">
            <x-heroicon-o-light-bulb class="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-400" aria-hidden="true" />

            <div class="text-xs leading-relaxed text-amber-800 dark:text-amber-300">
                <p class="mb-0.5 font-semibold">Catatan</p>
                <p>{{ $catatan }}</p>
            </div>
        </div>
    @endif
</aside>
