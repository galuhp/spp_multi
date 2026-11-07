<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\EntriPembayaranKasir;
use App\Filament\Resources\Spps\SppResource;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\Spp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KategoriTarifTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $petugas;

    protected User $siswaUser;

    protected Siswa $siswa;

    protected Spp $sppTarif;

    protected Spp $uangGedungTarif;

    protected Spp $uangSeragamTarif;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'username' => 'admin_tarif',
            'role' => UserRole::Admin,
        ]);

        $this->petugas = User::factory()->create([
            'username' => 'petugas_tarif',
            'role' => UserRole::Petugas,
        ]);

        $jurusan = Jurusan::create([
            'kode' => 'RPL',
            'nama' => 'Rekayasa Perangkat Lunak',
            'deskripsi' => 'Software engineering',
        ]);

        $kelas = Kelas::create([
            'nama_kelas' => 'X RPL 1',
            'tingkat' => '10',
            'jurusan_id' => $jurusan->id,
        ]);

        // SPP tariff for 2026/2027
        $this->sppTarif = Spp::create([
            'kategori' => Spp::KATEGORI_SPP,
            'tipe' => Spp::TIPE_BULANAN,
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'nominal' => 250000,
            'keterangan' => 'Tarif SPP TA 2026/2027',
        ]);

        // Uang Gedung tariff for 2026/2027 (same year/tahun)
        $this->uangGedungTarif = Spp::create([
            'kategori' => Spp::KATEGORI_UANG_GEDUNG,
            'tipe' => Spp::TIPE_SEKALI_BAYAR,
            'nama' => 'Uang Gedung & Pengembangan',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'nominal' => 2000000,
            'keterangan' => 'DSP uang gedung',
        ]);

        // Uang Seragam tariff for 2026/2027 (same year/tahun)
        $this->uangSeragamTarif = Spp::create([
            'kategori' => Spp::KATEGORI_UANG_SERAGAM,
            'tipe' => Spp::TIPE_SEKALI_BAYAR,
            'nama' => 'Paket Seragam 4 Stel',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'nominal' => 750000,
            'keterangan' => 'Seragam sekolah lengkap',
        ]);

        $this->siswaUser = User::factory()->create([
            'username' => '0091122334',
            'role' => UserRole::Siswa,
        ]);

        $this->siswa = Siswa::create([
            'user_id' => $this->siswaUser->id,
            'kelas_id' => $kelas->id,
            'spp_id' => $this->sppTarif->id,
            'nisn' => '0091122334',
            'nis' => '2026888',
            'nama' => 'Dimas Anggara',
            'jenis_kelamin' => 'L',
            'alamat' => 'Bandung',
            'no_telp' => '081234567899',
        ]);
    }

    public function test_admin_can_create_and_filter_various_fee_categories(): void
    {
        // Admin creates Biaya Praktik for 2026
        $response = $this->actingAs($this->admin)->post(route('spp.store'), [
            'kategori' => 'Biaya Praktik',
            'tipe' => 'sekali_bayar',
            'nama' => 'Biaya Praktikum Komputer',
            'tahun' => 2026,
            'tahun_ajaran' => '2026/2027',
            'nominal' => 500000,
            'keterangan' => 'Alat dan modul praktikum',
        ]);

        $response->assertRedirect(route('spp.index'));
        $this->assertDatabaseHas('spps', [
            'kategori' => 'Biaya Praktik',
            'nominal' => 500000,
        ]);

        // Filter index by category is forwarded to the Filament table filter.
        $this->actingAs($this->admin)
            ->get(route('spp.index', ['kategori' => 'Uang Gedung']))
            ->assertRedirect(SppResource::getUrl('index', [
                'filters' => ['kategori' => ['value' => 'Uang Gedung']],
            ]));

        $filterResponse = $this->actingAs($this->admin)
            ->followingRedirects()
            ->get(route('spp.index', ['kategori' => 'Uang Gedung']));
        $filterResponse->assertOk();
        $filterResponse->assertSee('Uang Gedung & Pengembangan');
        $filterResponse->assertDontSee('Biaya Praktikum Komputer');
    }

    public function test_payment_form_displays_non_spp_fee_options_for_student(): void
    {
        $this->actingAs($this->petugas)
            ->get(route('pembayaran.create', ['siswa_id' => $this->siswa->id]))
            ->assertRedirect(EntriPembayaranKasir::getUrl().'?siswa_id='.$this->siswa->id);

        $component = Livewire::test(EntriPembayaranKasir::class)
            ->call('pilihSiswa', $this->siswa->id);

        $nonSpp = collect($component->instance()->kasirData['non_spp']);

        $this->assertTrue($nonSpp->contains(fn (array $item): bool => str_contains($item['nama'], 'Uang Gedung & Pengembangan') && $item['sisa'] === 2000000));
        $this->assertTrue($nonSpp->contains(fn (array $item): bool => str_contains($item['nama'], 'Paket Seragam 4 Stel') && $item['sisa'] === 750000));
    }

    public function test_staff_can_pay_non_spp_fees_like_uang_gedung_and_uang_seragam(): void
    {
        // 1. Pay Uang Seragam in full
        $seragamResponse = $this->actingAs($this->petugas)->post(route('pembayaran.store'), [
            'jenis_transaksi' => 'non_spp',
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->uangSeragamTarif->id,
            'jumlah_bayar' => 750000,
            'tgl_bayar' => '2026-11-03',
            'metode_pembayaran' => 'Tunai',
            'catatan' => 'Lunas seragam',
        ]);

        $this->assertDatabaseHas('pembayarans', [
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->uangSeragamTarif->id,
            'jumlah_bayar' => 750000,
            'bulan_dibayar' => null,
        ]);

        $pembayaranSeragam = Pembayaran::where('spp_id', $this->uangSeragamTarif->id)->first();
        $seragamResponse->assertRedirect(route('pembayaran.show', $pembayaranSeragam->id));

        // 2. Pay Uang Gedung partially (cicilan 1: Rp 1.000.000 dari Rp 2.000.000)
        $gedungResponse = $this->actingAs($this->petugas)->post(route('pembayaran.store'), [
            'jenis_transaksi' => 'non_spp',
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->uangGedungTarif->id,
            'jumlah_bayar' => 1000000,
            'tgl_bayar' => '2026-11-03',
            'metode_pembayaran' => 'Transfer Bank',
            'catatan' => 'Cicilan ke-1 Uang Gedung',
        ]);

        $this->assertDatabaseHas('pembayarans', [
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->uangGedungTarif->id,
            'jumlah_bayar' => 1000000,
        ]);

        // Attempt to pay more than remaining balance for Uang Gedung (sisa 1.000.000, attempts 1.500.000)
        $overpayResponse = $this->actingAs($this->petugas)->post(route('pembayaran.store'), [
            'jenis_transaksi' => 'non_spp',
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->uangGedungTarif->id,
            'jumlah_bayar' => 1500000,
            'tgl_bayar' => '2026-11-03',
            'metode_pembayaran' => 'Tunai',
        ]);
        $overpayResponse->assertSessionHas('error');
    }

    public function test_printed_receipt_and_public_verification_show_correct_fee_category(): void
    {
        $pembayaran = Pembayaran::create([
            'kode_transaksi' => 'GEDUNG-20261103-0001',
            'user_id' => $this->petugas->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->uangGedungTarif->id,
            'tgl_bayar' => '2026-11-03',
            'bulan_dibayar' => null,
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 2000000,
            'metode_pembayaran' => 'Transfer Bank',
            'catatan' => 'Pelunasan uang gedung',
        ]);

        // Receipt print view
        $printResponse = $this->actingAs($this->petugas)->get(route('pembayaran.cetak', $pembayaran));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('Uang Gedung & Pengembangan');
        $printResponse->assertSee('Rp 2.000.000');

        // Public QR verification link (unauthenticated parent)
        $publicResponse = $this->get($pembayaran->buktiUrl());
        $publicResponse->assertStatus(200);
        $publicResponse->assertSee('Bukti Pembayaran Terverifikasi Sah');
        $publicResponse->assertSee('Uang Gedung & Pengembangan');
        $publicResponse->assertSee('Rp 2.000.000');
    }

    public function test_student_portal_displays_non_spp_tariffs_and_billing_status(): void
    {
        // Pay Uang Seragam
        Pembayaran::create([
            'kode_transaksi' => 'SRG-20261103-0001',
            'user_id' => $this->petugas->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->uangSeragamTarif->id,
            'tgl_bayar' => '2026-11-03',
            'bulan_dibayar' => null,
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 750000,
            'metode_pembayaran' => 'Tunai',
        ]);

        $tagihanResponse = $this->actingAs($this->siswaUser)->followingRedirects()->get(route('portal.tagihan'));
        $tagihanResponse->assertOk();
        $tagihanResponse->assertSee('Pos Tagihan Sekali Bayar (Non-SPP)');
        $tagihanResponse->assertSee('Uang Gedung & Pengembangan');
        $tagihanResponse->assertSee('Belum Lunas');
        $tagihanResponse->assertSee('Paket Seragam 4 Stel');

        $riwayatResponse = $this->actingAs($this->siswaUser)->followingRedirects()->get(route('portal.riwayat'));
        $riwayatResponse->assertOk();
        $riwayatResponse->assertSee('Riwayat Pembayaran SPP Saya');
        $riwayatResponse->assertSee('Paket Seragam 4 Stel');
        $riwayatResponse->assertSee('Rp 750.000');
    }

    public function test_laporan_can_be_filtered_by_category(): void
    {
        // Record SPP payment
        Pembayaran::create([
            'kode_transaksi' => 'SPP-20261103-9001',
            'user_id' => $this->petugas->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->sppTarif->id,
            'tgl_bayar' => '2026-11-03',
            'bulan_dibayar' => 'Juli',
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 250000,
            'metode_pembayaran' => 'Tunai',
        ]);

        // Record Uang Gedung payment
        Pembayaran::create([
            'kode_transaksi' => 'GDG-20261103-9002',
            'user_id' => $this->petugas->id,
            'siswa_id' => $this->siswa->id,
            'spp_id' => $this->uangGedungTarif->id,
            'tgl_bayar' => '2026-11-03',
            'bulan_dibayar' => null,
            'tahun_dibayar' => 2026,
            'jumlah_bayar' => 2000000,
            'metode_pembayaran' => 'Transfer Bank',
        ]);

        // Filter report by Uang Gedung
        $reportResponse = $this->actingAs($this->petugas)->followingRedirects()->get(route('laporan.index', [
            'kategori' => Spp::KATEGORI_UANG_GEDUNG,
        ]));
        $reportResponse->assertOk();
        $reportResponse->assertSee('GDG-20261103-9002');
        $reportResponse->assertDontSee('SPP-20261103-9001');

        // Printable report by category
        $printReportResponse = $this->actingAs($this->petugas)->get(route('laporan.cetak', [
            'kategori' => Spp::KATEGORI_UANG_GEDUNG,
        ]));
        $printReportResponse->assertStatus(200);
        $printReportResponse->assertSee('Uang Gedung');
        $printReportResponse->assertSee('GDG-20261103-9002');
        $printReportResponse->assertSee('Terbilang:');
        $printReportResponse->assertSee('Dua juta rupiah');
    }
}
