import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Menyimpan JWT di Keychain (iOS) / EncryptedSharedPreferences (Android).
class TokenStorage {
  TokenStorage([FlutterSecureStorage? storage])
      : _storage = storage ?? const FlutterSecureStorage();

  static const _key = 'access_token';
  final FlutterSecureStorage _storage;
  String? _sessionToken;

  Future<String?> read() async => _sessionToken ?? await _storage.read(key: _key);

  /// [persist] false = token hanya di memori (sesi aplikasi), hilang saat ditutup.
  Future<void> write(String token, {bool persist = true}) async {
    if (persist) {
      _sessionToken = null;
      await _storage.write(key: _key, value: token);
    } else {
      _sessionToken = token;
      await _storage.delete(key: _key);
    }
  }

  Future<void> clear() async {
    _sessionToken = null;
    await _storage.delete(key: _key);
  }
}

final tokenStorageProvider = Provider<TokenStorage>((ref) => TokenStorage());
