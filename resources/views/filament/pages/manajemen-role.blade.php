<x-filament-panels::page>
    @push('styles')
        @vite('resources/css/manajemen-role.css')
    @endpush

    @php
        $users = $this->users;
        $permissions = [
            'Dashboard' => ['admin' => true, 'petugas' => true, 'siswa' => true],
            'Transaksi SPP' => ['admin' => true, 'petugas' => true, 'siswa' => false],
            'Portal Siswa' => ['admin' => true, 'petugas' => true, 'siswa' => true],
            'Kesiswaan' => ['admin' => true, 'petugas' => true, 'siswa' => false],
            'Master Data' => ['admin' => true, 'petugas' => true, 'siswa' => false],
            'Pengaturan & Akses' => ['admin' => true, 'petugas' => false, 'siswa' => false],
            'Manajemen Role' => ['admin' => true, 'petugas' => false, 'siswa' => false],
        ];
    @endphp

    <div class="space-y-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-5">
            <h3 class="text-base font-semibold text-zinc-900">Ringkasan Role</h3>
            <p class="mt-0.5 text-xs text-zinc-500">Role menentukan modul yang dapat diakses pengguna di panel admin.</p>

            <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                @foreach ($this->roles as $item)
                    <div class="rounded-xl border border-zinc-200 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-semibold text-zinc-900">{{ $item['label'] }}</span>
                            <x-ui.badge variant="outline">{{ $item['jumlah'] }} pengguna</x-ui.badge>
                        </div>
                        <p class="mt-2 text-xs text-zinc-500">{{ $item['deskripsi'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5">
            <h3 class="text-base font-semibold text-zinc-900">Matriks Hak Akses</h3>
            <p class="mt-0.5 text-xs text-zinc-500">Ringkasan modul yang tersedia untuk setiap role.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-600">
                        <tr>
                            <th class="px-3 py-2">Modul</th>
                            <th class="px-3 py-2 text-center">Administrator</th>
                            <th class="px-3 py-2 text-center">Petugas</th>
                            <th class="px-3 py-2 text-center">Siswa</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @foreach ($permissions as $modul => $akses)
                            <tr>
                                <td class="px-3 py-2 font-medium text-zinc-800">{{ $modul }}</td>
                                @foreach (['admin', 'petugas', 'siswa'] as $role)
                                    <td class="px-3 py-2 text-center">
                                        @if ($akses[$role])
                                            <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Boleh</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-500">Tidak</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900">Pengguna & Role</h3>
                    <p class="mt-0.5 text-xs text-zinc-500">Ubah role pengguna. Perubahan dicatat di log aktivitas.</p>
                </div>

                <div class="w-full sm:w-72">
                    <label for="cari-pengguna" class="mb-1 block text-xs font-medium text-zinc-600">Cari pengguna</label>
                    <input id="cari-pengguna" type="search" wire:model.live.debounce.300ms="cari"
                        placeholder="Nama, username, atau email..."
                        class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200">
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-600">
                        <tr>
                            <th class="px-3 py-2">Nama</th>
                            <th class="px-3 py-2">Username</th>
                            <th class="px-3 py-2">Email</th>
                            <th class="px-3 py-2">Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100">
                        @forelse ($users as $user)
                            <tr wire:key="user-{{ $user->id }}">
                                <td class="px-3 py-2 font-medium text-zinc-900">{{ $user->name }}</td>
                                <td class="px-3 py-2 font-mono text-xs text-zinc-600">{{ $user->username }}</td>
                                <td class="px-3 py-2 text-xs text-zinc-500">{{ $user->email ?: '-' }}</td>
                                <td class="px-3 py-2">
                                    <select wire:change="ubahRole({{ $user->id }}, $event.target.value)"
                                        class="w-44 rounded-lg border border-zinc-300 px-2 py-1.5 text-xs focus:border-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-200">
                                        @foreach (\App\Enums\UserRole::cases() as $role)
                                            <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-xs text-zinc-500">Tidak ada pengguna yang cocok.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>