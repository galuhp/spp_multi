import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Menyimpan JWT, role, dan profil sesi di Keychain (iOS) / EncryptedSharedPreferences (Android).
class TokenStorage {
  TokenStorage([FlutterSecureStorage? storage])
      : _storage = storage ?? const FlutterSecureStorage();

  static const _key = 'access_token';
  static const _roleKey = 'user_role';
  static const _nameKey = 'user_name';

  final FlutterSecureStorage _storage;
  String? _sessionToken;
  String? _sessionRole;
  String? _sessionName;

  Future<String?> read() async => _sessionToken ?? await _storage.read(key: _key);

  Future<String?> readRole() async =>
      _sessionRole ?? await _storage.read(key: _roleKey);

  Future<String?> readName() async =>
      _sessionName ?? await _storage.read(key: _nameKey);

  /// [persist] false = token hanya di memori (sesi aplikasi), hilang saat ditutup.
  Future<void> write(
    String token, {
    String role = 'siswa',
    String name = '',
    bool persist = true,
  }) async {
    if (persist) {
      _sessionToken = null;
      _sessionRole = null;
      _sessionName = null;
      await _storage.write(key: _key, value: token);
      await _storage.write(key: _roleKey, value: role);
      await _storage.write(key: _nameKey, value: name);
    } else {
      _sessionToken = token;
      _sessionRole = role;
      _sessionName = name;
      await _storage.delete(key: _key);
      await _storage.delete(key: _roleKey);
      await _storage.delete(key: _nameKey);
    }
  }

  Future<void> clear() async {
    _sessionToken = null;
    _sessionRole = null;
    _sessionName = null;
    await _storage.delete(key: _key);
    await _storage.delete(key: _roleKey);
    await _storage.delete(key: _nameKey);
  }
}

final tokenStorageProvider = Provider<TokenStorage>((ref) => TokenStorage());
