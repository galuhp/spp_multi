import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Preferensi lokal aplikasi (terpisah dari data sesi/JWT).
class AppSettingsStorage {
  AppSettingsStorage([FlutterSecureStorage? storage])
      : _storage = storage ?? const FlutterSecureStorage();

  static const _biometricLockKey = 'biometric_lock_enabled';

  final FlutterSecureStorage _storage;

  /// Apakah kunci biometrik diaktifkan pengguna.
  Future<bool> readBiometricLock() async =>
      await _storage.read(key: _biometricLockKey) == 'true';

  Future<void> writeBiometricLock(bool enabled) =>
      _storage.write(key: _biometricLockKey, value: enabled.toString());
}
