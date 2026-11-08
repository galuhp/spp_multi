<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\LaporanPengeluaran;
use App\Models\PengeluaranSekolah;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Pengeluaran\PengeluaranService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PengeluaranVoidTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TahunAjaran $tahunAjaran;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->tahunAjaran = TahunAjaran::query()->updateOrCreate(
            ['nama' => '2026/2027'],
            [
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
                'aktif' => true,
            ],
        );

        $this->actingAs($this->admin);
    }

    public function test_pembatalan_menyimpan_riwayat_dan_mencatat_log(): void
    {
        $pengeluaran = $this->buatPengeluaran('Pembelian ATK');

        app(PengeluaranService::class)->batalkan(
            $pengeluaran,
            $this->admin->id,
            'Salah input nominal.',
        );

        $pengeluaran->refresh();

        $this->assertNotNull($pengeluaran->void_at);
        $this->assertSame($this->admin->id, $pengeluaran->void_oleh);
        $this->assertSame('Salah input nominal.', $pengeluaran->void_alasan);
        $this->assertDatabaseHas('pengeluaran_sekolahs', ['id' => $pengeluaran->id]);

        $this->assertDatabaseHas('log_aktivitas', [
            'aksi' => 'pengeluaran.void',
            'subjek_id' => $pengeluaran->id,
        ]);
    }

    public function test_pengeluaran_yang_sudah_batal_tidak_dibatalkan_ulang(): void
    {
        $pengeluaran = $this->buatPengeluaran('Perbaikan jaringan');

        $service = app(PengeluaranService::class);
        $service->batalkan($pengeluaran, $this->admin->id, 'Alasan pertama.');
        $service->batalkan($pengeluaran->fresh(), $this->admin->id, 'Alasan kedua.');

        $this->assertSame('Alasan pertama.', $pengeluaran->fresh()->void_alasan);
    }

    public function test_pengeluaran_batal_tidak_tampil_di_laporan_pengeluaran(): void
    {
        $valid = $this->buatPengeluaran('Bayar listrik');
        $batal = $this->buatPengeluaran('Konsumsi rapat');

        app(PengeluaranService::class)->batalkan($batal, $this->admin->id, 'Duplikat input.');

        Livewire::test(LaporanPengeluaran::class)
            ->assertSee($valid->nomor_transaksi)
            ->assertDontSee($batal->nomor_transaksi);
    }

    private function buatPengeluaran(string $keperluan): PengeluaranSekolah
    {
        return PengeluaranSekolah::query()->create([
            'tanggal' => now()->toDateString(),
            'kategori' => PengeluaranSekolah::KATEGORI_OPERASIONAL,
            'keperluan' => $keperluan,
            'nominal' => 150000,
            'metode_pembayaran' => PengeluaranSekolah::METODE_TUNAI,
            'user_id' => $this->admin->id,
            'tahun_ajaran_id' => $this->tahunAjaran->id,
        ]);
    }
}
