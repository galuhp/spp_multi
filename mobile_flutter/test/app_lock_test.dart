import 'package:flutter_test/flutter_test.dart';
import 'package:espp_mobile/core/security/app_lock_controller.dart';
import 'package:espp_mobile/core/security/biometric_service.dart';
import 'package:espp_mobile/core/storage/app_settings_storage.dart';

class FakeBiometric implements BiometricAuth {
  FakeBiometric({this.available = true, this.result = true});

  bool available;
  bool result;
  int attempts = 0;

  @override
  Future<bool> isAvailable() async => available;

  @override
  Future<bool> authenticate({
    String localizedReason =
        'Pindai sidik jari atau Face ID untuk masuk ke E-SPP',
  }) async {
    attempts++;
    return result;
  }
}

class FakeSettings extends AppSettingsStorage {
  FakeSettings({this.enabled = false});

  bool enabled;

  @override
  Future<bool> readBiometricLock() async => enabled;

  @override
  Future<void> writeBiometricLock(bool value) async {
    enabled = value;
  }
}

void main() {
  group('AppLockController - preferensi & kunci awal', () {
    test('membaca preferensi kunci biometrik tersimpan', () async {
      final controller = AppLockController(
        FakeBiometric(),
        FakeSettings(enabled: true),
      );
      await controller.ready;

      expect(controller.state.enabled, isTrue);
      expect(controller.state.isLocked, isFalse);
    });

    test('mengunci saat aplikasi dibuka bila kunci aktif', () async {
      final controller = AppLockController(
        FakeBiometric(),
        FakeSettings(enabled: true),
      );
      await controller.ready;
      await controller.lockOnStart();

      expect(controller.state.status, AppLockStatus.locked);
    });

    test('tidak mengunci saat aplikasi dibuka bila kunci nonaktif', () async {
      final controller = AppLockController(
        FakeBiometric(),
        FakeSettings(),
      );
      await controller.ready;
      await controller.lockOnStart();

      expect(controller.state.isLocked, isFalse);
    });

    test('mengaktifkan kunci menyimpan preferensi', () async {
      final settings = FakeSettings();
      final controller = AppLockController(FakeBiometric(), settings);
      await controller.ready;

      await controller.setEnabled(true);

      expect(settings.enabled, isTrue);
      expect(controller.state.enabled, isTrue);
    });

    test('menonaktifkan kunci langsung membuka aplikasi', () async {
      final settings = FakeSettings(enabled: true);
      final controller = AppLockController(FakeBiometric(), settings);
      await controller.ready;
      await controller.lockOnStart();
      expect(controller.state.isLocked, isTrue);

      await controller.setEnabled(false);

      expect(settings.enabled, isFalse);
      expect(controller.state.isLocked, isFalse);
    });
  });

  group('AppLockController - verifikasi biometrik', () {
    test('biometrik berhasil membuka kunci', () async {
      final biometric = FakeBiometric(result: true);
      final controller = AppLockController(
        biometric,
        FakeSettings(enabled: true),
      );
      await controller.ready;
      await controller.lockOnStart();

      final unlocked = await controller.unlockWithBiometrics();

      expect(unlocked, isTrue);
      expect(controller.state.status, AppLockStatus.unlocked);
      expect(controller.state.error, isNull);
      expect(biometric.attempts, 1);
    });

    test('biometrik gagal tetap terkunci dan menampilkan pesan', () async {
      final controller = AppLockController(
        FakeBiometric(result: false),
        FakeSettings(enabled: true),
      );
      await controller.ready;
      await controller.lockOnStart();

      final unlocked = await controller.unlockWithBiometrics();

      expect(unlocked, isFalse);
      expect(controller.state.status, AppLockStatus.locked);
      expect(controller.state.error, contains('gagal'));
    });

    test('biometrik tidak tersedia tidak membuka kunci', () async {
      final controller = AppLockController(
        FakeBiometric(available: false),
        FakeSettings(enabled: true),
      );
      await controller.ready;
      await controller.lockOnStart();

      expect(await controller.biometricAvailable(), isFalse);
      expect(await controller.unlockWithBiometrics(), isFalse);
      expect(controller.state.isLocked, isTrue);
    });
  });

  group('AppLockController - auto-lock saat kembali dari background', () {
    test('mengunci setelah 2 menit di background', () async {
      var now = DateTime(2026, 11, 13, 8);
      final controller = AppLockController(
        FakeBiometric(),
        FakeSettings(enabled: true),
        clock: () => now,
      );
      await controller.ready;
      await controller.lockOnStart();
      await controller.unlockWithBiometrics();
      expect(controller.state.isLocked, isFalse);

      controller.onBackgrounded();
      now = now.add(AppLockController.autoLockAfter);
      await controller.onResumed();

      expect(controller.state.status, AppLockStatus.locked);
    });

    test('tidak mengunci bila kembali sebelum 2 menit', () async {
      var now = DateTime(2026, 11, 13, 8);
      final controller = AppLockController(
        FakeBiometric(),
        FakeSettings(enabled: true),
        clock: () => now,
      );
      await controller.ready;
      await controller.lockOnStart();
      await controller.unlockWithBiometrics();

      controller.onBackgrounded();
      now = now.add(const Duration(seconds: 30));
      await controller.onResumed();

      expect(controller.state.isLocked, isFalse);
    });

    test('tidak mengunci bila fitur kunci nonaktif', () async {
      var now = DateTime(2026, 11, 13, 8);
      final controller = AppLockController(
        FakeBiometric(),
        FakeSettings(),
        clock: () => now,
      );
      await controller.ready;

      controller.onBackgrounded();
      now = now.add(const Duration(minutes: 10));
      await controller.onResumed();

      expect(controller.state.isLocked, isFalse);
    });
  });

  group('AppLockController - logout', () {
    test('unlock() melepas kunci tanpa verifikasi', () async {
      final controller = AppLockController(
        FakeBiometric(),
        FakeSettings(enabled: true),
      );
      await controller.ready;
      await controller.lockOnStart();

      controller.unlock();

      expect(controller.state.status, AppLockStatus.unlocked);
    });
  });
}
