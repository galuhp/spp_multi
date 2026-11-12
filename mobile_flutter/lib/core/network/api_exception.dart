import 'package:dio/dio.dart';

/// Error ramah pengguna dari amplop `{success:false, message, code}`.
class ApiException implements Exception {
  ApiException(this.message, {this.code, this.statusCode});

  final String message;
  final String? code;
  final int? statusCode;

  factory ApiException.fromDio(DioException e) {
    final data = e.response?.data;
    if (data is Map && data['message'] is String) {
      return ApiException(
        data['message'] as String,
        code: data['code'] as String?,
        statusCode: e.response?.statusCode,
      );
    }
    return ApiException(
      'Tidak dapat terhubung ke server. Periksa koneksi internet Anda.',
      statusCode: e.response?.statusCode,
    );
  }

  @override
  String toString() => message;
}
