/// Konfigurasi endpoint backend E-SPP (Go/Gin, prefix `/api/v1`).
class ApiConstants {
  ApiConstants._();

  /// Ganti via `--dart-define=API_BASE_URL=https://spp.sekolah.sch.id/api/v1`.
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8080/api/v1',
  );

  static const String login = '/public/login';
  static const String studentDashboard = '/siswa/dashboard';
  static const String studentCard = '/siswa/kartu-spp';
  static String studentReceipt(String kode) => '/siswa/kuitansi/$kode';
  static String publicReceipt(String kode, String token) =>
      '/public/bukti/$kode/$token';
}
