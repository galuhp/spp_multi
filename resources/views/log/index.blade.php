@extends('layouts.app')

@section('title', 'Log Aktivitas')
@section('header_title', 'Log Aktivitas Sistem')

@section('content')
<div class="space-y-6">
    <div>
        <h3 class="text-xl font-bold text-slate-900 dark:text-white">Log Aktivitas</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Jejak audit transaksi pembayaran, tarif SPP, dan pengelolaan pengguna</p>
    </div>

    <div class="prism-card p-4">
        <form action="{{ route('log.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <select name="modul" class="px-3.5 py-2 prism-input text-sm">
                <option value="">-- Semua Modul --</option>
                @foreach($modulList as $key => $label)
                    <option value="{{ $key }}" {{ $modul === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="user_id" class="px-3.5 py-2 prism-input text-sm">
                <option value="">-- Semua Pengguna --</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (string) $userId === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>

            <input type="date" name="tgl_awal" value="{{ $tglAwal }}" title="Tanggal Awal"
                class="px-3.5 py-2 prism-input text-sm">
            <input type="date" name="tgl_akhir" value="{{ $tglAkhir }}" title="Tanggal Akhir"
                class="px-3.5 py-2 prism-input text-sm">

            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 prism-btn-primary text-xs">Filter</button>
                @if($modul || $userId || $tglAwal || $tglAkhir)
                    <a href="{{ route('log.index') }}" class="px-3 py-2 prism-btn-secondary text-xs">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="prism-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="prism-thead">
                        <th class="py-3 px-4">Waktu</th>
                        <th class="py-3 px-4">Pengguna</th>
                        <th class="py-3 px-4">Aksi</th>
                        <th class="py-3 px-4">Deskripsi</th>
                        <th class="py-3 px-4">IP</th>
                        <th class="py-3 px-4 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="prism-tbody">
                    @forelse($logs as $log)
                        <tr class="prism-tr align-top">
                            <td class="py-3.5 px-4 text-xs text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-3.5 px-4 text-xs font-semibold text-slate-900 dark:text-white">
                                {{ $log->user?->name ?? 'Sistem / akun dihapus' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold font-mono {{ $log->badgeClass() }}">
                                    {{ $log->aksi }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-slate-700 dark:text-slate-300">{{ $log->deskripsi }}</td>
                            <td class="py-3.5 px-4 text-xs font-mono text-slate-500 dark:text-slate-400">{{ $log->ip_address ?? '-' }}</td>
                            <td class="py-3.5 px-4 text-center">
                                @if($log->data)
                                    <details class="text-left">
                                        <summary class="cursor-pointer text-xs font-semibold text-prism-600 dark:text-prism-300 hover:underline text-center">Lihat</summary>
                                        <pre class="mt-2 p-2.5 rounded-xl bg-slate-950 text-slate-100 border border-white/10 text-[11px] overflow-x-auto max-w-xs">{{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    <span class="text-xs text-slate-300 dark:text-slate-600">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500 text-sm">Belum ada aktivitas yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-white/10">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
