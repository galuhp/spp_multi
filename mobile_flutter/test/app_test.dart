import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:espp_mobile/core/network/dio_client.dart';
import 'package:espp_mobile/core/storage/token_storage.dart';
import 'package:espp_mobile/core/utils/formatters.dart';
import 'package:espp_mobile/features/auth/auth_controller.dart';
import 'package:espp_mobile/features/tagihan/tagihan_models.dart';
import 'package:flutter/material.dart' show Brightness, Color;
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';

class _MemoryStorage extends TokenStorage {
  String? token;
  bool? lastPersist;

  @override
  Future<String?> read() async => token;

  @override
  Future<void> write(
    String t, {
    String role = 'siswa',
    String name = '',
    bool persist = true,
  }) async {
    token = t;
    lastPersist = persist;
  }

  @override
  Future<void> clear() async => token = null;
}

class _FakeAdapter implements HttpClientAdapter {
  _FakeAdapter(this.status, this.body);

  final int status;
  final Map<String, dynamic> body;

  @override
  Future<ResponseBody> fetch(RequestOptions options,
      Stream<List<int>>? requestStream, Future<void>? cancelFuture) async {
    return ResponseBody.fromString(jsonEncode(body), status, headers: {
      Headers.contentTypeHeader: ['application/json'],
    });
  }

  @override
  void close({bool force = false}) {}
}

ProviderContainer _container(_MemoryStorage storage, int status, Map<String, dynamic> body) {
  final dio = Dio(BaseOptions(baseUrl: 'http://test'))
    ..httpClientAdapter = _FakeAdapter(status, body);
  return ProviderContainer(overrides: [
    tokenStorageProvider.overrideWithValue(storage),
    dioProvider.overrideWithValue(dio),
  ]);
}

double _contrast(Color a, Color b) {
  final la = a.computeLuminance();
  final lb = b.computeLuminance();
  final hi = la > lb ? la : lb;
  final lo = la > lb ? lb : la;
  return (hi + 0.05) / (lo + 0.05);
}

void main() {
  test('warna status memenuhi kontras teks WCAG AA (4.5:1) di kedua tema', () {
    const darkSurface = Color(0xFF1E293B);
    for (final s in StatusTagihan.values) {
      expect(_contrast(s.color(Brightness.light), s.background(Brightness.light)),
          greaterThanOrEqualTo(4.5),
          reason: '${s.name} light');
      final darkBg = Color.alphaBlend(s.background(Brightness.dark), darkSurface);
      expect(_contrast(s.color(Brightness.dark), darkBg), greaterThanOrEqualTo(4.5),
          reason: '${s.name} dark');
    }
  });

  test('StatusTagihan.parse memetakan status backend', () {
    expect(StatusTagihan.parse('lunas'), StatusTagihan.lunas);
    expect(StatusTagihan.parse('tunggakan'), StatusTagihan.belum);
    expect(StatusTagihan.parse('ditanggung_pemerintah'), StatusTagihan.ditanggung);
    expect(StatusTagihan.parse(null), StatusTagihan.akanDatang);
  });

  test('Formatters.rupiah memakai format Indonesia', () async {
    await initializeDateFormatting('id_ID');
    expect(Formatters.rupiah(1500000).replaceAll('\u00a0', ' '), 'Rp 1.500.000');
  });

  group('AuthController', () {
    test('login sukses menyimpan token dan signedIn', () async {
      final storage = _MemoryStorage();
      final c = _container(storage, 200, {
        'success': true,
        'message': 'ok',
        'data': {'token': 'jwt-1', 'role': 'siswa', 'name': 'Budi'},
      });
      addTearDown(c.dispose);
      await Future<void>.delayed(Duration.zero);
      await c.read(authControllerProvider.notifier).login('123', 'rahasia');
      expect(storage.token, 'jwt-1');
      expect(c.read(authControllerProvider), AuthStatus.signedIn);
    });

    test('login tanpa Ingat Saya tidak menyimpan permanen', () async {
      final storage = _MemoryStorage();
      final c = _container(storage, 200, {
        'success': true,
        'message': 'ok',
        'data': {'token': 'jwt-2', 'role': 'siswa', 'name': 'Budi'},
      });
      addTearDown(c.dispose);
      await c
          .read(authControllerProvider.notifier)
          .login('123', 'rahasia', remember: false);
      expect(storage.lastPersist, isFalse);
    });

    test('login gagal melempar pesan backend', () async {
      final storage = _MemoryStorage();
      final c = _container(storage, 401, {
        'success': false,
        'message': 'Kredensial salah',
        'code': 'UNAUTHENTICATED',
        'data': null,
      });
      addTearDown(c.dispose);
      await expectLater(
        c.read(authControllerProvider.notifier).login('x', 'y'),
        throwsA(predicate((e) => e.toString() == 'Kredensial salah')),
      );
      expect(storage.token, isNull);
    });

    test('logout menghapus token', () async {
      final storage = _MemoryStorage()..token = 'abc';
      final c = _container(storage, 200, {});
      addTearDown(c.dispose);
      await c.read(authControllerProvider.notifier).logout();
      expect(storage.token, isNull);
      expect(c.read(authControllerProvider), AuthStatus.signedOut);
    });
  });
}
