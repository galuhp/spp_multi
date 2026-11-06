<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\TagihanSiswa;
use App\Filament\Resources\Pembayarans\Pages\CreatePembayaran;
use App\Filament\Resources\Siswas\SiswaResource;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Support\Pembayaran\PembayaranService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PembayaranVoidDanSebagianTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private TahunAjaran $tahunAjaran;

    private Kelas $kelas;

    private Spp $spp;

    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-05 09:00:00'));

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->tahunAjaran = TahunAjaran::query()->updateOrCreate(
            ['nama' => '2026/2027'],
            [
                'tanggal_mulai' => '2026-07-01',
                'tanggal_selesai' => '2027-06-30',
                'aktif' => true,
            ],
        );

        $jurusan = Jurusan::query()->create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
        ]);

        $this->kelas = Kelas::query()->create([
            'tahun_ajaran_id' => $this->tahunAjaran->id,
            'jurusan_id' => $jurusan->id,
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
        ]);

        $this->spp = Spp::query()->create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'nama' => 'SPP',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'tingkat' => '10',
            'nominal' => 250000,
        ]);

        $this->siswa = Siswa::query()->create([
            'kelas_id' => $this->kelas->id,
            'spp_id' => $this->spp->id,
            'nisn' => '0071234561',
            'nis' => 'NIS0071234561',
            'nama' => 'Ahmad Rifai',
            'jenis_kelamin' => 'L',
        ]);

        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        $this->travelBack();

        parent::tearDown();
    }

    public function test_void_menyimpan_transaksi_dan_verifikasi_publik_menolak_transaksi_void(): void
    {
        $pembayaran = $this->bayar('Juli', 250000);

        app(PembayaranService::class)->voidPembayaran(
            $pembayaran,
            $this->admin->id,
            'Salah input nominal.',
        );

        $pembayaran->refresh();

        $this->assertNotNull($pembayaran->void_at);
        $this->assertSame('Salah input nominal.', $pembayaran->void_alasan);
        $this->assertDatabaseHas('pembayarans', ['id' => $pembayaran->id]);

        $this->get(route('bukti.pembayaran', [
            'kode' => $pembayaran->kode_transaksi,
            'token' => $pembayaran->verificationToken(),
        ]))
            ->assertStatus(410)
            ->assertSee('Pembayaran Telah Dibatalkan')
            ->assertSee('Salah input nominal.');
    }

    public function test_bulan_yang_sudah_di_void_dapat_dibayar_ulang(): void
    {
        $pembayaran = $this->bayar('Juli', 250000);

        app(PembayaranService::class)->voidPembayaran(
            $pembayaran,
            $this->admin->id,
            'Koreksi bulan.',
        );

        Livewire::test(CreatePembayaran::class)
            ->fillForm([
                'cara_pilih' => 'cari',
                'siswa_id' => $this->siswa->id,
                'bayar_spp' => ['spp'],
                'bulan_dibayar' => ['Juli'],
                'tgl_bayar' => '2026-11-05',
                'metode_pembayaran' => 'Tunai',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Pembayaran::query()
            ->where('siswa_id', $this->siswa->id)
            ->where('bulan_dibayar', 'Juli')
            ->whereNull('void_at')
            ->count());
    }

    public function test_pembayaran_sebagian_menyisakan_saldo_dan_bisa_dilunasi(): void
    {
        $this->bayar('Juli', 100000);

        $tagihan = collect(app(PembayaranService::class)->tagihanTerbuka($this->siswa))
            ->firstWhere('key', "spp:{$this->spp->id}:Juli:2026");

        $this->assertNotNull($tagihan);
        $this->assertSame(100000, $tagihan['dibayar']);
        $this->assertSame(150000, $tagihan['sisa']);

        Livewire::test(CreatePembayaran::class)
            ->fillForm([
                'cara_pilih' => 'cari',
                'siswa_id' => $this->siswa->id,
                'bayar_spp' => ['spp'],
                'bulan_dibayar' => ['Juli'],
                'tgl_bayar' => '2026-11-05',
                'metode_pembayaran' => 'Tunai',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('pembayarans', [
            'siswa_id' => $this->siswa->id,
            'bulan_dibayar' => 'Juli',
            'jumlah_bayar' => 150000,
            'void_at' => null,
        ]);
    }

    public function test_halaman_tagihan_menampilkan_status_sebagian(): void
    {
        $this->bayar('Juli', 100000);

        $this->get(TagihanSiswa::getUrl())
            ->assertOk()
            ->assertSee('Sebagian')
            ->assertSee('Sisa Rp 150.000');
    }

    public function test_siswa_dengan_riwayat_pembayaran_diarsipkan_bukan_dihapus(): void
    {
        $pembayaran = $this->bayar('Juli', 250000);

        $this->assertFalse(SiswaResource::canDelete($this->siswa));

        $this->delete(route('siswa.destroy', $this->siswa))
            ->assertRedirect(route('siswa.index'));

        $this->assertDatabaseHas('siswas', [
            'id' => $this->siswa->id,
            'is_archived' => true,
        ]);
        $this->assertDatabaseHas('pembayarans', ['id' => $pembayaran->id]);
    }

    private function bayar(string $bulan, int $jumlah): Pembayaran
    {
        return Pembayaran::query()->create([
            'kode_transaksi' => 'TRX'.uniqid(),
            'user_id' => $this->admin->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->spp->id,
            'tgl_bayar' => '2026-11-05',
            'bulan_dibayar' => $bulan,
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => $jumlah,
            'metode_pembayaran' => 'Tunai',
        ]);
    }
}
