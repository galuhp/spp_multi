<?php

namespace App\Filament\Resources\PembayaranGabungans\Pages;

use App\Filament\Resources\PembayaranGabungans\PembayaranGabunganResource;
use App\Models\PembayaranGabungan;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreatePembayaranGabungan extends CreateRecord
{
    protected static string $resource = PembayaranGabunganResource::class;

    public function getTitle(): string
    {
        return 'Buat Pembayaran Massal';
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['kode'] = $this->kodeBaru();
        $data['status'] = PembayaranGabungan::STATUS_DRAFT;
        $data['idempotency_token'] = (string) Str::uuid();
        $data['dibuat_oleh'] = auth()->id();
        $data['total_diterima'] = (int) ($data['total_diterima'] ?? 0);

        return PembayaranGabungan::query()->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }

    protected function kodeBaru(): string
    {
        $prefix = 'PG-'.now()->format('Ymd').'-';
        $jumlah = PembayaranGabungan::query()->where('kode', 'like', $prefix.'%')->count();

        return $prefix.str_pad((string) ($jumlah + 1), 4, '0', STR_PAD_LEFT);
    }
}
