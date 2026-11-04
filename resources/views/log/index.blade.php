@extends('layouts.app')

@section('title', 'Log Aktivitas')
@section('header_title', 'Log Aktivitas Sistem')

@section('content')
<div class="space-y-6">
    <div>
        <h3 class="text-xl font-bold text-zinc-900">Log Aktivitas</h3>
        <p class="text-xs text-zinc-500 mt-1">Jejak audit transaksi pembayaran, tarif SPP, dan pengelolaan pengguna</p>
    </div>

    <div class="bg-white p-4 rounded-xl border border-zinc-200 shadow-xs">
        <form action="{{ route('log.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <select name="modul" class="px-3.5 py-2 rounded-xl border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-zinc-400">
                <option value="">-- Semua Modul --</option>
                @foreach($modulList as $key => $label)
                    <option value="{{ $key }}" {{ $modul === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="user_id" class="px-3.5 py-2 rounded-xl border border-zinc-300 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-zinc-400">
                <option value="">-- Semua Pengguna --</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ (string) $userId === (string) $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>

            <input type="date" name="tgl_awal" value="{{ $tglAwal }}" title="Tanggal Awal"
                class="px-3.5 py-2 rounded-xl border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-400">
            <input type="date" name="tgl_akhir" value="{{ $tglAkhir }}" title="Tanggal Akhir"
                class="px-3.5 py-2 rounded-xl border border-zinc-300 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-400">

            <div class="flex items-center gap-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-zinc-900 hover:bg-zinc-800 text-white text-xs font-semibold transition">Filter</button>
                @if($modul || $userId || $tglAwal || $tglAkhir)
                    <a href="{{ route('log.index') }}" class="px-3 py-2 rounded-xl bg-zinc-100 hover:bg-zinc-200 text-zinc-700 text-xs font-semibold transition">Reset</a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-zinc-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50 text-zinc-600 text-xs font-semibold uppercase">
                        <th class="py-3 px-4">Waktu</th>
                        <th class="py-3 px-4">Pengguna</th>
                        <th class="py-3 px-4">Aksi</th>
                        <th class="py-3 px-4">Deskripsi</th>
                        <th class="py-3 px-4">IP</th>
                        <th class="py-3 px-4 text-center">Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 text-zinc-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-zinc-50 transition align-top">
                            <td class="py-3.5 px-4 text-xs text-zinc-600 whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-3.5 px-4 text-xs font-semibold text-zinc-900">
                                {{ $log->user?->name ?? 'Sistem / akun dihapus' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-semibold font-mono {{ $log->badgeClass() }}">
                                    {{ $log->aksi }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-xs text-zinc-700">{{ $log->deskripsi }}</td>
                            <td class="py-3.5 px-4 text-xs font-mono text-zinc-500">{{ $log->ip_address ?? '-' }}</td>
                            <td class="py-3.5 px-4 text-center">
                                @if($log->data)
                                    <details class="text-left">
                                        <summary class="cursor-pointer text-xs font-semibold text-zinc-900 hover:text-zinc-900 text-center">Lihat</summary>
                                        <pre class="mt-2 p-2 rounded-lg bg-zinc-900 text-slate-100 text-[11px] overflow-x-auto max-w-xs">{{ json_encode($log->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    </details>
                                @else
                                    <span class="text-xs text-zinc-300">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-zinc-400 text-sm">Belum ada aktivitas yang tercatat.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-zinc-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
