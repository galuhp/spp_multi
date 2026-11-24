<?php

namespace App\Filament\SuperAdmin\Resources\TenantResource\Pages;

use App\Enums\UserRole;
use App\Filament\SuperAdmin\Resources\TenantResource;
use App\Models\TahunAjaran;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateTenant extends CreateRecord
{
    protected static string $resource = TenantResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $createInitialAdmin = (bool) ($data['create_initial_admin'] ?? false);
        $adminData = [
            'name' => $data['admin_name'] ?? null,
            'username' => $data['admin_username'] ?? null,
            'email' => $data['admin_email'] ?? null,
            'password' => $data['admin_password'] ?? null,
        ];

        $createInitialTahunAjaran = (bool) ($data['create_initial_tahun_ajaran'] ?? false);
        $tahunAjaranData = [
            'nama' => $data['tahun_ajaran_nama'] ?? null,
            'tanggal_mulai' => $data['tahun_ajaran_mulai'] ?? null,
            'tanggal_selesai' => $data['tahun_ajaran_selesai'] ?? null,
        ];

        unset(
            $data['create_initial_admin'],
            $data['admin_name'],
            $data['admin_username'],
            $data['admin_email'],
            $data['admin_password'],
            $data['create_initial_tahun_ajaran'],
            $data['tahun_ajaran_nama'],
            $data['tahun_ajaran_mulai'],
            $data['tahun_ajaran_selesai'],
        );

        return DB::transaction(function () use (
            $data,
            $createInitialAdmin,
            $adminData,
            $createInitialTahunAjaran,
            $tahunAjaranData,
        ): Tenant {
            /** @var Tenant $tenant */
            $tenant = Tenant::create($data);

            if ($createInitialAdmin && filled($adminData['username']) && filled($adminData['password'])) {
                $admin = User::create([
                    'name' => $adminData['name'],
                    'username' => $adminData['username'],
                    'email' => $adminData['email'],
                    'password' => $adminData['password'],
                    'role' => UserRole::Admin,
                    'is_super_admin' => false,
                ]);

                $tenant->users()->attach($admin->id, ['role' => UserRole::Admin->value]);
            }

            if ($createInitialTahunAjaran && filled($tahunAjaranData['nama'])) {
                try {
                    TenantContext::set($tenant);
                    TahunAjaran::create([
                        'nama' => $tahunAjaranData['nama'],
                        'tanggal_mulai' => $tahunAjaranData['tanggal_mulai'],
                        'tanggal_selesai' => $tahunAjaranData['tanggal_selesai'],
                        'aktif' => true,
                    ]);
                } finally {
                    TenantContext::clear();
                }
            }

            return $tenant;
        });
    }
}
