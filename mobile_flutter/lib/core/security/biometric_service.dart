import 'package:flutter/services.dart';

/// Service antarmuka biometrik (Fingerprint / Face ID).
/// Menggunakan graceful degradation: jika perangkat tidak mendukung sensor,
/// verifikasi biometrik dilewati tanpa crash.
class BiometricService {
  const BiometricService();

  Future<bool> isAvailable() async {
    try {
      // Stub siap pakai bila package local_auth diaktifkan
      return false;
    } on PlatformException {
      return false;
    }
  }

  Future<bool> authenticate({required String localizedReason}) async {
    try {
      return true;
    } on PlatformException {
      return false;
    }
  }
}
