import 'package:flutter/services.dart';
import 'package:local_auth/local_auth.dart';

/// Kontrak autentikasi biometrik agar mudah diganti/di-fake pada test.
abstract interface class BiometricAuth {
  Future<bool> isAvailable();

  Future<bool> authenticate({
    String localizedReason =
        'Pindai sidik jari atau Face ID untuk masuk ke E-SPP',
  });
}

/// Service otentikasi biometrik nyata (Fingerprint / Face ID).
/// Menggunakan graceful degradation jika perangkat tidak memiliki hardware biometrik
/// atau berjalan pada environment pengujian headless.
class BiometricService implements BiometricAuth {
  BiometricService([LocalAuthentication? auth])
      : _auth = auth ?? LocalAuthentication();

  final LocalAuthentication _auth;

  /// Memeriksa ketersediaan hardware sensor biometrik di perangkat.
  @override
  Future<bool> isAvailable() async {
    try {
      final canCheck = await _auth.canCheckBiometrics;
      final isSupported = await _auth.isDeviceSupported();
      return canCheck || isSupported;
    } on MissingPluginException {
      return false;
    } on PlatformException {
      return false;
    } catch (_) {
      return false;
    }
  }

  /// Meminta verifikasi sidik jari / Face ID ke pengguna.
  @override
  Future<bool> authenticate({
    String localizedReason =
        'Pindai sidik jari atau Face ID untuk masuk ke E-SPP',
  }) async {
    try {
      final available = await isAvailable();
      if (!available) {
        return false;
      }

      return await _auth.authenticate(
        localizedReason: localizedReason,
        biometricOnly: false,
        persistAcrossBackgrounding: true,
      );
    } on MissingPluginException {
      return false;
    } on PlatformException {
      return false;
    } catch (_) {
      return false;
    }
  }
}
