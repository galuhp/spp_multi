import 'package:flutter/material.dart';

import '../../core/theme/app_theme.dart';

enum StatusTagihan {
  lunas('Lunas', AppColors.success, AppColors.successBg, AppColors.successDark),
  sebagian('Sebagian', AppColors.warning, AppColors.warningBg,
      AppColors.warningDark),
  belum('Belum Lunas', AppColors.danger, AppColors.dangerBg,
      AppColors.dangerDark),
  akanDatang('Akan Datang', AppColors.neutral, AppColors.neutralBg,
      AppColors.neutralDark),
  ditanggung('Ditanggung Pemerintah', AppColors.subsidi, AppColors.subsidiBg,
      AppColors.subsidiDark);

  const StatusTagihan(this.label, this._light, this._lightBg, this._dark);

  final String label;
  final Color _light;
  final Color _lightBg;
  final Color _dark;

  /// Warna teks/ikon status; kontras >= 4.5:1 di kedua tema.
  Color color(Brightness b) => b == Brightness.dark ? _dark : _light;

  /// Background pastel (terang) atau tint transparan (gelap).
  Color background(Brightness b) =>
      b == Brightness.dark ? _dark.withValues(alpha: 0.16) : _lightBg;

  static StatusTagihan parse(String? raw) => switch (raw) {
        'lunas' => lunas,
        'sebagian' => sebagian,
        'belum' || 'belum_lunas' || 'tunggakan' => belum,
        'ditanggung' || 'ditanggung_pemerintah' => ditanggung,
        _ => akanDatang,
      };
}

class TagihanBulanItem {
  TagihanBulanItem({
    required this.bulan,
    required this.tahun,
    required this.nominal,
    required this.dibayar,
    required this.status,
    this.noKuitansi,
  });

  final String bulan;
  final int tahun;
  final num nominal;
  final num dibayar;
  final StatusTagihan status;
  final String? noKuitansi;

  num get sisa => nominal - dibayar;

  factory TagihanBulanItem.fromJson(Map<String, dynamic> j) => TagihanBulanItem(
        bulan: j['bulan'] as String? ?? '-',
        tahun: (j['tahun'] as num?)?.toInt() ?? 0,
        nominal: j['nominal'] as num? ?? 0,
        dibayar: j['dibayar'] as num? ?? 0,
        status: StatusTagihan.parse(j['status'] as String?),
        noKuitansi: j['no_kuitansi'] as String?,
      );
}

class TagihanNonSppItem {
  TagihanNonSppItem({
    required this.namaPos,
    required this.nominal,
    required this.dibayar,
    required this.status,
  });

  final String namaPos;
  final num nominal;
  final num dibayar;
  final StatusTagihan status;

  double get progress =>
      nominal == 0 ? 0 : (dibayar / nominal).clamp(0, 1).toDouble();

  factory TagihanNonSppItem.fromJson(Map<String, dynamic> j) =>
      TagihanNonSppItem(
        namaPos: j['nama_pos'] as String? ?? '-',
        nominal: j['nominal'] as num? ?? 0,
        dibayar: j['dibayar'] as num? ?? 0,
        status: StatusTagihan.parse(j['status'] as String?),
      );
}

/// Satu baris rincian pembayaran pada kuitansi (pos, tahun ajaran, subsidi).
class PembayaranRincian {
  const PembayaranRincian({
    required this.keterangan,
    required this.jumlah,
    this.tahunAjaran,
    this.subsidiPersen,
  });

  final String keterangan;
  final num jumlah;
  final String? tahunAjaran;
  final num? subsidiPersen;

  factory PembayaranRincian.fromJson(Map<String, dynamic> j) =>
      PembayaranRincian(
        keterangan: j['keterangan'] as String? ?? 'Pembayaran SPP',
        jumlah: j['jumlah'] as num? ?? 0,
        tahunAjaran: j['tahun_ajaran'] as String?,
        subsidiPersen: j['subsidi_persen'] as num?,
      );
}

class PembayaranItem {
  PembayaranItem({
    required this.kode,
    required this.tanggal,
    required this.jumlah,
    required this.metode,
    required this.petugas,
    required this.isVoid,
    this.tokenVerifikasi,
    this.nis,
    this.waktu,
    this.voidAlasan,
    this.tahunAjaran,
    this.rincian = const [],
  });

  final String kode;
  final DateTime tanggal;
  final num jumlah;
  final String metode;
  final String petugas;
  final bool isVoid;
  final String? tokenVerifikasi;
  final String? nis;

  /// Waktu transaksi lengkap (created_at) untuk mencetak jam.
  final DateTime? waktu;
  final String? voidAlasan;
  final String? tahunAjaran;
  final List<PembayaranRincian> rincian;

  /// Rincian yang selalu bisa dicetak: bila API belum mengirim rincian,
  /// gunakan satu baris ringkasan dari nominal transaksi.
  List<PembayaranRincian> get rincianEfektif => rincian.isNotEmpty
      ? rincian
      : [
          PembayaranRincian(
            keterangan: 'Pembayaran SPP / Biaya Sekolah',
            jumlah: jumlah,
            tahunAjaran: tahunAjaran,
          ),
        ];

  factory PembayaranItem.fromJson(Map<String, dynamic> j) {
    final rawRincian = (j['rincian'] ?? j['items']) as List?;
    return PembayaranItem(
      kode: j['kode_transaksi'] as String? ?? '-',
      tanggal:
          DateTime.tryParse(j['tgl_bayar'] as String? ?? '') ?? DateTime.now(),
      jumlah: j['jumlah_bayar'] as num? ?? 0,
      metode: j['metode'] as String? ?? '-',
      petugas: j['petugas'] as String? ?? '-',
      isVoid: j['status'] == 'void',
      tokenVerifikasi: j['token'] as String?,
      nis: j['nis'] as String?,
      waktu: DateTime.tryParse(j['created_at'] as String? ?? ''),
      voidAlasan: j['void_alasan'] as String?,
      tahunAjaran: j['tahun_ajaran'] as String?,
      rincian: (rawRincian ?? const [])
          .map((e) => PembayaranRincian.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

/// Respons `GET /siswa/kartu-spp`.
class KartuSpp {
  KartuSpp({required this.spp, required this.nonSpp, required this.pembayaran});

  final List<TagihanBulanItem> spp;
  final List<TagihanNonSppItem> nonSpp;
  final List<PembayaranItem> pembayaran;

  static List<T> _list<T>(dynamic raw, T Function(Map<String, dynamic>) f) =>
      ((raw as List?) ?? const [])
          .map((e) => f(e as Map<String, dynamic>))
          .toList();

  factory KartuSpp.fromJson(Map<String, dynamic> j) => KartuSpp(
        spp: _list(j['spp'], TagihanBulanItem.fromJson),
        nonSpp: _list(j['non_spp'], TagihanNonSppItem.fromJson),
        pembayaran: _list(j['pembayaran'], PembayaranItem.fromJson),
      );
}
