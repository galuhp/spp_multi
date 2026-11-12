/// Ringkasan keuangan siswa. Sesuaikan key dengan respons
/// `GET /siswa/dashboard` saat handler backend selesai.
class RingkasanKeuangan {
  RingkasanKeuangan({
    required this.namaSiswa,
    required this.nisn,
    required this.kelas,
    required this.tunggakanJatuhTempo,
    required this.totalTagihan,
    required this.totalDibayar,
  });

  final String namaSiswa;
  final String nisn;
  final String kelas;
  final num tunggakanJatuhTempo;
  final num totalTagihan;
  final num totalDibayar;

  double get progress =>
      totalTagihan == 0 ? 0 : (totalDibayar / totalTagihan).clamp(0, 1).toDouble();

  factory RingkasanKeuangan.fromJson(Map<String, dynamic> json) =>
      RingkasanKeuangan(
        namaSiswa: json['nama'] as String? ?? '-',
        nisn: json['nisn'] as String? ?? '',
        kelas: json['kelas'] as String? ?? '-',
        tunggakanJatuhTempo: json['tagihan_jatuh_tempo'] as num? ?? 0,
        totalTagihan: json['total_tagihan'] as num? ?? 0,
        totalDibayar: json['total_dibayar'] as num? ?? 0,
      );
}
