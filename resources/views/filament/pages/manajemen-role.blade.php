<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/manajemen-role.css')
    @endpush

    @php
        $users = $this->users;
        $modulAkses = \App\Models\RolePermission::AVAILABLE;
    @endphp

    <div class="space-y-5">
        <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Ringkasan Role</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Role menentukan modul yang dapat diakses pengguna di panel admin.</p>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                @foreach ($this->roles as $item)
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-gray-700 dark:bg-gray-800/40">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-slate-900 dark:text-white">{{ $item['label'] }}</span>
                            <x-ui.badge variant="outline">{{ $item['jumlah'] }} pengguna</x-ui.badge>
                        </div>
                        <p class="mt-2 text-xs text-slate-500 dark:text-gray-400">{{ $item['deskripsi'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Matriks Hak Akses</h3>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Centang modul yang boleh diakses tiap role. Manajemen Role tetap khusus Administrator.</p>
                </div>
                <x-filament::button type="button" size="sm" wire:click="simpanAkses">
                    Simpan Hak Akses
                </x-filament::button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-600 dark:bg-gray-800/60 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2">Modul</th>
                            @foreach (\App\Enums\UserRole::cases() as $role)
                                <th class="px-3 py-2 text-center">{{ $role->label() }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                        @foreach ($modulAkses as $kunci => $label)
                            <tr>
                                <td class="px-3 py-2 font-medium text-slate-800 dark:text-gray-200">{{ $label }}</td>
                                @foreach (\App\Enums\UserRole::cases() as $role)
                                    <td class="px-3 py-2 text-center">
                                        <input type="checkbox" wire:model="akses.{{ $role->value }}.{{ $kunci }}"
                                            class="size-4 rounded border-slate-300 accent-primary-600 dark:border-gray-600">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr class="bg-slate-50/70 dark:bg-gray-800/30">
                            <td class="px-3 py-2 font-medium text-slate-800 dark:text-gray-200">Manajemen Role</td>
                            @foreach (\App\Enums\UserRole::cases() as $role)
                                <td class="px-3 py-2 text-center">
                                    <input type="checkbox" disabled @checked($role === \App\Enums\UserRole::Admin)
                                        class="size-4 rounded border-slate-300 accent-primary-600 dark:border-gray-600">
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="fi-section rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs dark:border-white/10 dark:bg-gray-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900 dark:text-white">Pengguna & Role</h3>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-gray-400">Ubah role pengguna. Perubahan dicatat di log aktivitas.</p>
                </div>

                <div class="w-full sm:w-72">
                    <label for="cari-pengguna" class="mb-1 block text-xs font-medium text-slate-600 dark:text-gray-300">Cari pengguna</label>
                    <input id="cari-pengguna" type="search" wire:model.live.debounce.300ms="cari"
                        placeholder="Nama, username, atau email..."
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-600 dark:bg-gray-800/60 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2">Nama</th>
                            <th class="px-3 py-2">Username</th>
                            <th class="px-3 py-2">Email</th>
                            <th class="px-3 py-2">Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-800">
                        @forelse ($users as $user)
                            <tr wire:key="user-{{ $user->id }}">
                                <td class="px-3 py-2 font-medium text-slate-900 dark:text-white">{{ $user->name }}</td>
                                <td class="px-3 py-2 font-mono text-xs text-slate-600 dark:text-gray-300">{{ $user->username }}</td>
                                <td class="px-3 py-2 text-xs text-slate-500 dark:text-gray-400">{{ $user->email ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    <select wire:change="ubahRole({{ $user->id }}, $event.target.value)"
                                        class="w-44 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs text-slate-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                        @foreach (\App\Enums\UserRole::cases() as $role)
                                            <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-xs text-slate-500 dark:text-gray-400">Tidak ada pengguna yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>