<x-filament-panels::page>
    <style>
        /* Styling khusus tipografi dokumen panduan */
        .panduan-content h1 {
            font-size: 1.65rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .dark .panduan-content h1 {
            color: #f8fafc;
            border-color: #334155;
        }
        .panduan-content h2 {
            font-size: 1.35rem;
            font-weight: 600;
            color: #1e293b;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.35rem;
            border-bottom: 1px solid #cbd5e1;
        }
        .dark .panduan-content h2 {
            color: #f1f5f9;
            border-color: #475569;
        }
        .panduan-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #334155;
            margin-top: 1.25rem;
            margin-bottom: 0.5rem;
        }
        .dark .panduan-content h3 {
            color: #e2e8f0;
        }
        .panduan-content h4 {
            font-size: 0.95rem;
            font-weight: 600;
            color: #475569;
            margin-top: 1rem;
            margin-bottom: 0.35rem;
        }
        .dark .panduan-content h4 {
            color: #cbd5e1;
        }
        .panduan-content p {
            margin-bottom: 0.85rem;
            line-height: 1.65;
            color: #334155;
        }
        .dark .panduan-content p {
            color: #cbd5e1;
        }
        .panduan-content ul, .panduan-content ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        .panduan-content ul {
            list-style-type: disc;
        }
        .panduan-content ol {
            list-style-type: decimal;
        }
        .panduan-content li {
            margin-bottom: 0.35rem;
            line-height: 1.55;
            color: #334155;
        }
        .dark .panduan-content li {
            color: #cbd5e1;
        }
        .panduan-content table {
            width: 100%;
            margin-top: 1rem;
            margin-bottom: 1.5rem;
            border-collapse: collapse;
            font-size: 0.85rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .dark .panduan-content table {
            border-color: #334155;
        }
        .panduan-content th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 600;
            text-align: left;
            padding: 0.65rem 0.85rem;
            border: 1px solid #e2e8f0;
        }
        .dark .panduan-content th {
            background-color: #1e293b;
            color: #f8fafc;
            border-color: #334155;
        }
        .panduan-content td {
            padding: 0.6rem 0.85rem;
            border: 1px solid #e2e8f0;
            color: #334155;
            vertical-align: top;
        }
        .dark .panduan-content td {
            border-color: #334155;
            color: #cbd5e1;
        }
        .panduan-content code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.825rem;
            padding: 0.15rem 0.35rem;
            border-radius: 0.25rem;
            background-color: #f1f5f9;
            color: #0284c7;
        }
        .dark .panduan-content code {
            background-color: #1e293b;
            color: #38bdf8;
        }
        .panduan-content pre {
            background-color: #0f172a;
            color: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            margin-bottom: 1rem;
            font-size: 0.825rem;
        }
        .panduan-content pre code {
            background-color: transparent;
            color: inherit;
            padding: 0;
        }
        .panduan-content blockquote {
            border-left: 4px solid #3b82f6;
            background-color: #eff6ff;
            padding: 0.75rem 1rem;
            margin-top: 1rem;
            margin-bottom: 1rem;
            border-radius: 0 0.5rem 0.5rem 0;
        }
        .dark .panduan-content blockquote {
            border-color: #60a5fa;
            background-color: rgba(30, 58, 138, 0.25);
        }
        .panduan-content hr {
            margin-top: 2rem;
            margin-bottom: 2rem;
            border: 0;
            border-top: 1px dashed #cbd5e1;
        }
        .dark .panduan-content hr {
            border-top-color: #475569;
        }

        /* Mode Cetak (Print Media Query) */
        @media print {
            .no-print,
            header,
            aside,
            .fi-sidebar,
            .fi-topbar,
            .fi-header,
            .fi-page-header-actions {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
                font-size: 11pt !important;
            }
            .panduan-content {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .panduan-content h1,
            .panduan-content h2 {
                page-break-after: avoid;
            }
            .panduan-content table,
            .panduan-content blockquote,
            .panduan-content pre {
                page-break-inside: avoid;
            }
        }
    </style>

    <div x-data="{
        cari: '',
        aktifBab: '',
        scrollTo(id) {
            const el = document.getElementById(id);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                this.aktifBab = id;
            }
        }
    }" class="space-y-6">

        {{-- Banner Selamat Datang --}}
        <div class="no-print relative overflow-hidden rounded-2xl bg-gradient-to-r from-primary-600 via-primary-700 to-indigo-800 p-6 text-white shadow-md">
            <div class="relative z-10 max-w-3xl space-y-2">
                <div class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-xs font-medium backdrop-blur-xs">
                    <span>📖 Dokumentasi Resmi Sistem E-SPP</span>
                    <span class="opacity-60">•</span>
                    <span>Versi Lengkap</span>
                </div>
                <h2 class="text-xl font-bold sm:text-2xl">Buku Panduan Penggunaan & SOP Operasional</h2>
                <p class="text-xs text-primary-100 sm:text-sm leading-relaxed">
                    Panduan komprehensif bagi Administrator, Kasir Keuangan, Wali Kelas, dan Siswa. Gunakan daftar isi di sebelah kiri untuk melompat langsung ke topik yang Anda butuhkan, atau klik tombol <strong>Cetak Panduan</strong> di kanan atas untuk mencetak buku pedoman resmi.
                </p>
            </div>
            <div class="absolute -right-6 -bottom-8 h-44 w-44 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
        </div>

        {{-- Konten Utama: 2 Kolom (Sidebar TOC + Isi Dokumen) --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 items-start">

            {{-- Kolom Kiri: Navigasi Daftar Bab (Sticky) --}}
            <div class="no-print lg:col-span-1 lg:sticky lg:top-4 space-y-3">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs dark:border-white/10 dark:bg-gray-900">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">
                            Daftar Isi Bab
                        </h3>
                        <span class="rounded bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary-600 dark:bg-primary-950/50 dark:text-primary-400">
                            9 Bab
                        </span>
                    </div>

                    <div class="space-y-1 text-sm max-h-[70vh] overflow-y-auto pr-1">
                        @foreach ($this->daftarBab as $bab)
                            <button
                                type="button"
                                @click="scrollTo('{{ $bab['id'] }}')"
                                :class="aktifBab === '{{ $bab['id'] }}'
                                    ? 'bg-primary-50 text-primary-700 font-semibold border-primary-500 dark:bg-primary-950/40 dark:text-primary-300'
                                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 border-transparent dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white'"
                                class="w-full text-left rounded-lg border-l-3 px-3 py-2 text-xs transition duration-150 flex flex-col gap-0.5">
                                <span class="font-medium truncate">{{ $bab['judul'] }}</span>
                                <span class="text-[10px] text-slate-400 dark:text-gray-400 line-clamp-1">{{ $bab['deskripsi'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800 text-center">
                        <a href="{{ route('panduan.unduh') }}" target="_blank"
                            class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400">
                            <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" />
                            <span>Unduh File .md Asli</span>
                        </a>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Tampilan Dokumen Panduan --}}
            <div class="lg:col-span-3">
                <div class="rounded-xl border border-slate-200 bg-white p-6 md:p-8 shadow-xs dark:border-white/10 dark:bg-gray-900">
                    <article class="panduan-content prose max-w-none dark:prose-invert">
                        {!! $this->kontenHtml !!}
                    </article>

                    <div class="no-print mt-10 pt-6 border-t border-slate-200 dark:border-gray-800 flex flex-wrap items-center justify-between gap-4 text-xs text-slate-500 dark:text-gray-400">
                        <div>
                            <span>Sistem E-SPP • Buku Panduan Resmi Versi 1.0</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })"
                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 font-medium hover:bg-slate-50 dark:border-gray-700 dark:hover:bg-gray-800">
                                <x-heroicon-o-arrow-up class="w-3.5 h-3.5" />
                                <span>Kembali ke Atas</span>
                            </button>
                            <button type="button" onclick="window.print()"
                                class="inline-flex items-center gap-1 rounded-lg bg-primary-600 px-3 py-1.5 font-medium text-white hover:bg-primary-700">
                                <x-heroicon-o-printer class="w-3.5 h-3.5" />
                                <span>Cetak Dokumen</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>
