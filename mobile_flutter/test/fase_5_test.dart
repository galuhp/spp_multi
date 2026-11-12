import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:espp_mobile/core/network/mock_interceptor.dart';
import 'package:espp_mobile/core/notifications/notification_service.dart';
import 'package:espp_mobile/features/wali/wali_models.dart';

void main() {
  group('Fase 5.1: NotificationService (Push & Local Reminders)', () {
    test('NotificationService inisialisasi graceful pada lingkungan headless', () async {
      final notif = NotificationService();
      // Pada headless testing, initialize tidak melempar uncaught exception
      final result = await notif.initialize();
      expect(result, isA<bool>());
    });

    test('scheduleMonthlyReminder berjalan graceful tanpa exception', () async {
      final notif = NotificationService();
      await expectLater(
        notif.scheduleMonthlyReminder(bulan: 'November 2026', nominal: 250000),
        completes,
      );
    });
  });

  group('Fase 5.2: Wali Kelas Models & WhatsApp Generator', () {
    test('SiswaWaliItem serialisasi & properti isLunas', () {
      final jsonMenunggak = {
        'id': 101,
        'nisn': '0065432190',
        'nis': '240101',
        'nama': 'Ahmad Fauzi',
        'no_hp_wali': '081234567890',
        'total_tunggakan': 500000,
        'bulan_menunggak': ['Oktober', 'November'],
        'status_bulan_ini': 'belum_lunas',
      };
      final siswaMenunggak = SiswaWaliItem.fromJson(jsonMenunggak);
      expect(siswaMenunggak.nama, 'Ahmad Fauzi');
      expect(siswaMenunggak.totalTunggakan, 500000);
      expect(siswaMenunggak.bulanMenunggak.length, 2);
      expect(siswaMenunggak.isLunas, false);

      final jsonLunas = {
        'id': 103,
        'nisn': '0065432192',
        'nis': '240103',
        'nama': 'Citra Lestari',
        'no_hp_wali': '085712345678',
        'total_tunggakan': 0,
        'bulan_menunggak': [],
        'status_bulan_ini': 'lunas',
      };
      final siswaLunas = SiswaWaliItem.fromJson(jsonLunas);
      expect(siswaLunas.isLunas, true);
    });

    test('generateWhatsAppMessage menyusun template pesan resmi tagihan SPP', () {
      final siswa = SiswaWaliItem(
        id: 101,
        nisn: '0065432190',
        nis: '240101',
        nama: 'Ahmad Fauzi',
        noHpWali: '081234567890',
        totalTunggakan: 500000,
        bulanMenunggak: ['Oktober', 'November'],
        statusBulanIni: 'belum_lunas',
      );

      final pesan = siswa.generateWhatsAppMessage(
        namaKelas: 'XII RPL 1',
        namaWali: 'Drs. Bambang Sudarsono, M.Pd',
      );

      expect(pesan, contains('Ahmad Fauzi'));
      expect(pesan, contains('0065432190'));
      expect(pesan, contains('XII RPL 1'));
      expect(pesan, contains('Oktober, November'));
      expect(pesan, contains('500.000'));
      expect(pesan, contains('Drs. Bambang Sudarsono, M.Pd'));
      expect(pesan, contains('E-SPP Mobile'));
    });

    test('RekapKelasSummary menghitung persentase pelunasan secara presisi', () {
      final summary = RekapKelasSummary(
        kelasId: '1',
        namaKelas: 'XII RPL 1',
        waliKelas: 'Drs. Bambang Sudarsono, M.Pd',
        totalSiswa: 32,
        siswaLunas: 24,
        siswaMenunggak: 8,
        totalTunggakan: 2000000,
        siswa: [],
      );

      expect(summary.persentaseLunas, 24 / 32);
      expect((summary.persentaseLunas * 100).toStringAsFixed(1), '75.0');
    });
  });

  group('Fase 5.2: Mock Data Contract Integrity untuk Wali Kelas', () {
    late Dio dio;

    setUp(() {
      dio = Dio(BaseOptions(baseUrl: 'http://test.local/api/v1'));
      dio.interceptors.add(MockInterceptor(enabled: true));
    });

    test('Login sebagai Wali mengembalikan role wali_kelas', () async {
      final response = await dio.post('/public/login', data: {
        'username': 'walikelas_12rpl1',
        'password': 'validpassword',
      });

      expect(response.statusCode, 200);
      final data = response.data['data'] as Map<String, dynamic>;
      expect(data['role'], 'wali_kelas');
      expect(data['name'], contains('Bambang'));
    });

    test('Endpoint /wali/rekap-kelas berhasil deserialisasi ke RekapKelasSummary', () async {
      final response = await dio.get('/wali/rekap-kelas/1');

      expect(response.statusCode, 200);
      final json = response.data['data'] as Map<String, dynamic>;
      final summary = RekapKelasSummary.fromJson(json);

      expect(summary.namaKelas, contains('XII'));
      expect(summary.totalSiswa, 32);
      expect(summary.siswaMenunggak, 6);
      expect(summary.siswa.length, greaterThanOrEqualTo(5));
      expect(summary.totalTunggakan, 2250000);
    });

    test('Endpoint /wali/tunggakan mengembalikan daftar siswa menunggak', () async {
      final response = await dio.get('/wali/tunggakan');

      expect(response.statusCode, 200);
      final json = response.data['data'] as Map<String, dynamic>;
      final list = (json['siswa'] as List<dynamic>)
          .map((e) => SiswaWaliItem.fromJson(e as Map<String, dynamic>))
          .toList();

      expect(list.isNotEmpty, true);
      for (final s in list) {
        expect(s.totalTunggakan, greaterThan(0));
      }
    });
  });
}
