import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../config/app_config.dart';
import '../constants/api_constants.dart';
import '../storage/token_storage.dart';
import 'api_exception.dart';
import 'mock_interceptor.dart';

/// Dipanggil saat server membalas 401 (token kedaluwarsa) agar UI logout.
final unauthorizedEventProvider = StateProvider<int>((ref) => 0);

class AuthInterceptor extends Interceptor {
  AuthInterceptor(this._storage, this._onUnauthorized);

  final TokenStorage _storage;
  final void Function() _onUnauthorized;

  @override
  Future<void> onRequest(
    RequestOptions options,
    RequestInterceptorHandler handler,
  ) async {
    final token = await _storage.read();
    if (token != null && !options.path.startsWith('/public')) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }

  @override
  Future<void> onError(
    DioException err,
    ErrorInterceptorHandler handler,
  ) async {
    if (err.response?.statusCode == 401 &&
        !err.requestOptions.path.startsWith('/public')) {
      await _storage.clear();
      _onUnauthorized();
    }
    handler.next(err);
  }
}

final dioProvider = Provider<Dio>((ref) {
  final baseUrl = AppConfig.sanitizeBaseUrl(ApiConstants.baseUrl);
  final dio = Dio(
    BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 20),
      headers: {'Accept': 'application/json'},
    ),
  );

  // Jika USE_MOCK aktif (default di demo), MockInterceptor merespons sebelum request keluar
  if (AppConfig.useMock) {
    dio.interceptors.add(MockInterceptor(enabled: true));
  }

  dio.interceptors.add(
    AuthInterceptor(
      ref.read(tokenStorageProvider),
      () => ref.read(unauthorizedEventProvider.notifier).state++,
    ),
  );
  dio.interceptors.add(LogInterceptor(requestBody: false, responseBody: false));
  return dio;
});

/// Membuka amplop `{success, message, data}` dan melempar [ApiException].
Future<T> unwrap<T>(
  Future<Response<dynamic>> request,
  T Function(dynamic data) parse,
) async {
  try {
    final res = await request;
    final body = res.data as Map<String, dynamic>;
    if (body['success'] != true) {
      throw ApiException(
        body['message'] as String? ?? 'Terjadi kesalahan.',
        code: body['code'] as String?,
        statusCode: res.statusCode,
      );
    }
    return parse(body['data']);
  } on DioException catch (e) {
    throw ApiException.fromDio(e);
  }
}
