import '../../core/utils/formatters.dart';

class SiswaWaliItem {
  SiswaWaliItem({
    required this.id,
    required this.nisn,
    required this.nis,
    required this.nama,
    required this.noHpWali,
    required this.totalTunggakan,
    required this.bulanMenunggak,
    required this.statusBulanIni,
  });

  final int id;
  final String nisn;
  final String nis;
  final String nama;
  final String noHpWali;
  final num totalTunggakan;
  final List<String> bulanMenunggak;
  final String statusBulanIni;

  bool get isLunas => totalTunggakan == 0 || statusBulanIni.toLowerCase() == 'lunas';

  String generateWhatsAppMessage({String namaKelas = '', String namaWali = ''}) {
    final rincian = bulanMenunggak.isEmpty ? 'bulan ini' : bulanMenunggak.join(', ');
    final kelasTxt = namaKelas.isNotEmpty ? ' di kelas $namaKelas' : '';
    final waliTxt = namaWali.isNotEmpty ? '\nWali Kelas: $namaWali' : '\nWali Kelas';

    return 'Yth. Bapak/Ibu Wali Murid dari $nama ($nisn)$kelasTxt,\n\n'
        'Kami menginformasikan bahwa kewajiban administrasi SPP sekolah untuk periode ($rincian) '
        'saat ini tercatat belum lunas dengan total tunggakan: Rp ${Rupiah.format(totalTunggakan)}.\n\n'
        'Mohon untuk melakukan penyelesaian administrasi melalui loket kasir sekolah atau transfer rekening resmi sekolah. '
        'Rincian dan bukti pembayaran digital dapat diakses melalui aplikasi E-SPP Mobile.\n\n'
        'Terima kasih atas perhatian dan kerja sama Bapak/Ibu.$waliTxt';
  }

  factory SiswaWaliItem.fromJson(Map<String, dynamic> json) => SiswaWaliItem(
        id: json['id'] as int? ?? 0,
        nisn: json['nisn']?.toString() ?? '',
        nis: json['nis']?.toString() ?? '',
        nama: json['nama']?.toString() ?? '',
        noHpWali: json['no_hp_wali']?.toString() ?? '',
        totalTunggakan: (json['total_tunggakan'] as num?) ?? 0,
        bulanMenunggak: (json['bulan_menunggak'] as List<dynamic>?)
                ?.map((e) => e.toString())
                .toList() ??
            [],
        statusBulanIni: json['status_bulan_ini']?.toString() ?? 'belum_lunas',
      );
}

class RekapKelasSummary {
  RekapKelasSummary({
    required this.kelasId,
    required this.namaKelas,
    required this.waliKelas,
    required this.totalSiswa,
    required this.siswaLunas,
    required this.siswaMenunggak,
    required this.totalTunggakan,
    required this.siswa,
  });

  final String kelasId;
  final String namaKelas;
  final String waliKelas;
  final int totalSiswa;
  final int siswaLunas;
  final int siswaMenunggak;
  final num totalTunggakan;
  final List<SiswaWaliItem> siswa;

  double get persentaseLunas =>
      totalSiswa == 0 ? 0.0 : (siswaLunas / totalSiswa).clamp(0.0, 1.0);

  factory RekapKelasSummary.fromJson(Map<String, dynamic> json) =>
      RekapKelasSummary(
        kelasId: json['kelas_id']?.toString() ?? '1',
        namaKelas: json['nama_kelas']?.toString() ?? 'Kelas',
        waliKelas: json['wali_kelas']?.toString() ?? 'Wali Kelas',
        totalSiswa: (json['total_siswa'] as num?)?.toInt() ?? 0,
        siswaLunas: (json['siswa_lunas'] as num?)?.toInt() ?? 0,
        siswaMenunggak: (json['siswa_menunggak'] as num?)?.toInt() ?? 0,
        totalTunggakan: (json['total_tunggakan'] as num?) ?? 0,
        siswa: (json['siswa'] as List<dynamic>?)
                ?.map((e) => SiswaWaliItem.fromJson(e as Map<String, dynamic>))
                .toList() ??
            [],
      );
}
