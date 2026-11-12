import 'package:dio/dio.dart';
import 'package:espp_mobile/core/config/app_config.dart';
import 'package:espp_mobile/core/network/dio_client.dart';
import 'package:espp_mobile/core/network/mock_interceptor.dart';
import 'package:espp_mobile/features/dashboard/dashboard_models.dart';
import 'package:espp_mobile/features/tagihan/tagihan_models.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('AppConfig & Production Security', () {
    test('sanitizeBaseUrl mengizinkan https', () {
      expect(AppConfig.sanitizeBaseUrl('https://api.sekolah.sch.id'),
          'https://api.sekolah.sch.id');
    });
  });

  group('MockInterceptor & Data Contract Integrity', () {
    late Dio dio;

    setUp(() {
      dio = Dio(BaseOptions(baseUrl: 'http://test/api/v1'));
      dio.interceptors.add(MockInterceptor(enabled: true));
    });

    test('Login mock berhasil mengembalikan token & nama', () async {
      final res = await unwrap(
        dio.post('/public/login', data: {'username': '0065432190', 'password': '123'}),
        (data) => data as Map<String, dynamic>,
      );

      expect(res['token'], isNotEmpty);
      expect(res['role'], 'siswa');
    });

    test('Dashboard mock deserialisasi ke RingkasanKeuangan', () async {
      final res = await unwrap(
        dio.get('/siswa/dashboard'),
        (data) => RingkasanKeuangan.fromJson(data as Map<String, dynamic>),
      );

      expect(res.namaSiswa, isNotEmpty);
      expect(res.tunggakanJatuhTempo, equals(250000));
      expect(res.totalTagihan, equals(3000000));
      expect(res.progress, closeTo(0.916, 0.01));
    });

    test('Kartu SPP mock deserialisasi 12 bulan SPP, Non-SPP, & Pembayaran', () async {
      final res = await unwrap(
        dio.get('/siswa/kartu-spp'),
        (data) => KartuSpp.fromJson(data as Map<String, dynamic>),
      );

      expect(res.spp.length, equals(12));
      expect(res.spp.first.bulan, 'Juli');
      expect(res.spp.first.status, StatusTagihan.lunas);
      expect(res.nonSpp.length, greaterThanOrEqualTo(2));
      expect(res.pembayaran.length, greaterThanOrEqualTo(2));
      expect(res.pembayaran.first.kode, startsWith('SPP-'));
    });
  });
}
