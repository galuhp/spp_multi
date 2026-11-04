<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Flux UI - E-SPP</title>
    @vite('resources/css/demo-flux.css')
    @fluxAppearance
</head>
<body class="min-h-full bg-slate-50 text-slate-900 antialiased">
    <div class="mx-auto max-w-5xl space-y-8 px-4 py-10">
        <header>
            <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600">Demo UI Kit</p>
            <h1 class="mt-1 text-2xl font-bold">Flux UI</h1>
            <p class="mt-1 text-sm text-slate-500">
                Komponen Livewire resmi. <a href="https://fluxui.dev" class="text-indigo-600 underline" target="_blank">fluxui.dev</a>
                — versi gratis hanya mencakup Button, Dropdown, Icon, Separator, dan Tooltip.
            </p>
        </header>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Tombol (komponen gratis)</h2>
            <div class="flex flex-wrap items-center gap-3">
                <flux:button variant="primary">Tombol Utama</flux:button>
                <flux:button variant="filled">Filled</flux:button>
                <flux:button variant="outline">Outline</flux:button>
                <flux:button variant="ghost">Ghost</flux:button>
                <flux:button variant="danger">Hapus</flux:button>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Ukuran & Ikon</h2>
            <div class="flex flex-wrap items-center gap-3">
                <flux:button size="sm" icon="plus">Tambah</flux:button>
                <flux:button size="xs" variant="ghost" icon="pencil">Ubah</flux:button>
                <flux:button variant="danger" icon="trash">Hapus</flux:button>
                <flux:button square icon="cog-6-tooth" />
                <flux:button square icon="magnifying-glass" variant="ghost" />
            </div>
            <flux:separator class="my-5" />
            <div class="flex items-center gap-4 text-slate-500">
                <flux:icon name="check-circle" class="text-emerald-600" />
                <flux:icon name="exclamation-triangle" class="text-amber-600" />
                <flux:icon name="x-circle" class="text-rose-600" />
                <flux:icon name="information-circle" class="text-blue-600" />
                <flux:icon name="user-group" />
                <flux:icon name="banknotes" />
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold">Tooltip</h2>
            <div class="flex flex-wrap gap-3">
                <flux:tooltip content="Menambah data baru">
                    <flux:button icon="plus" variant="outline">Tambah Data</flux:button>
                </flux:tooltip>
                <flux:tooltip content="Simpan perubahan">
                    <flux:button icon="check" variant="primary">Simpan</flux:button>
                </flux:tooltip>
            </div>
        </section>

        <div class="rounded-xl border border-dashed border-slate-300 bg-white/60 p-6 text-sm text-slate-500">
            Catatan: Card, Input, Select, Table, Modal, dan komponen lain di Flux termasuk tier <strong>Pro</strong> (berlisensi).
            Demo ini sengaja hanya memakai komponen gratis agar tidak error.
        </div>

        <p class="text-center text-xs text-slate-400">Demo sementara — <a href="{{ route('login') }}" class="underline">kembali ke login</a></p>
    </div>

    @fluxScripts
</body>
</html>