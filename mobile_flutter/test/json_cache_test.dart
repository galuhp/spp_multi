import 'package:dio/dio.dart';
import 'package:espp_mobile/core/network/dio_client.dart';
import 'package:espp_mobile/core/network/json_cache.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    FlutterSecureStorage.setMockInitialValues({});
  });

  group('JsonCache', () {
    test('menyimpan dan membaca kembali data', () async {
      final cache = JsonCache();
      await cache.write('tagihan', {
        'total': 3000000,
        'items': [
          {'bulan': 'Juli', 'nominal': 250000},
        ],
      });

      final value = await cache.read('tagihan');

      expect(value, isA<Map<String, dynamic>>());
      expect((value as Map)['total'], 3000000);
      expect((value['items'] as List).length, 1);
    });

    test('data kedaluwarsa tidak dikembalikan', () async {
      var now = DateTime(2026, 11, 13, 8);
      final cache = JsonCache(null, () => now);
      await cache.write('dashboard', {'total': 1});

      now = now.add(JsonCache.defaultMaxAge + const Duration(minutes: 1));

      expect(await cache.read('dashboard'), isNull);
    });

    test('data dalam batas umur masih dikembalikan', () async {
      var now = DateTime(2026, 11, 13, 8);
      final cache = JsonCache(null, () => now);
      await cache.write('dashboard', {'total': 1});

      now = now.add(const Duration(hours: 1));

      expect(await cache.read('dashboard'), {'total': 1});
    });

    test('menghapus cache', () async {
      final cache = JsonCache();
      await cache.write('kunci', {'a': 1});

      await cache.remove('kunci');

      expect(await cache.read('kunci'), isNull);
    });
  });

  group('unwrapWithCache', () {
    final requestOptions = RequestOptions(path: '/siswa/kartu-spp');

    test('menyimpan respons sukses lalu memakainya saat offline', () async {
      final cache = JsonCache();
      var calls = 0;

      final first = await unwrapWithCache<Map<String, dynamic>>(
        fetch: () async {
          calls++;
          return Response(
            requestOptions: requestOptions,
            data: {
              'success': true,
              'data': {'nama': 'M. Rizky Pratama'},
            },
          );
        },
        cacheKey: 'kartu',
        cache: cache,
        parse: (data) => data as Map<String, dynamic>,
      );

      expect(first['nama'], 'M. Rizky Pratama');

      final offline = await unwrapWithCache<Map<String, dynamic>>(
        fetch: () async => throw DioException(
          requestOptions: requestOptions,
          type: DioExceptionType.connectionError,
        ),
        cacheKey: 'kartu',
        cache: cache,
        parse: (data) => data as Map<String, dynamic>,
      );

      expect(calls, 1);
      expect(offline['nama'], 'M. Rizky Pratama');
    });

    test('tetap melempar error bila tidak ada cache', () async {
      expect(
        () => unwrapWithCache<Map<String, dynamic>>(
          fetch: () async => throw DioException(
            requestOptions: requestOptions,
            type: DioExceptionType.connectionError,
          ),
          cacheKey: 'kosong',
          cache: JsonCache(),
          parse: (data) => data as Map<String, dynamic>,
        ),
        throwsA(anything),
      );
    });
  });
}
