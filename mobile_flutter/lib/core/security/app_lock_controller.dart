import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/app_settings_storage.dart';
import 'biometric_service.dart';

enum AppLockStatus { unlocked, locked, authenticating }

/// Status kunci aplikasi + preferensi aktif/tidaknya kunci biometrik.
class AppLockState {
  const AppLockState({
    this.status = AppLockStatus.unlocked,
    this.enabled = false,
    this.error,
  });

  final AppLockStatus status;
  final bool enabled;
  final String? error;

  bool get isLocked => status != AppLockStatus.unlocked;

  AppLockState copyWith({
    AppLockStatus? status,
    bool? enabled,
    String? error,
    bool clearError = false,
  }) =>
      AppLockState(
        status: status ?? this.status,
        enabled: enabled ?? this.enabled,
        error: clearError ? null : (error ?? this.error),
      );
}

/// Mengatur kunci aplikasi berbasis biometrik:
/// - kunci saat aplikasi pertama dibuka (jika diaktifkan),
/// - kunci otomatis setelah [autoLockAfter] di background,
/// - buka kunci lewat sidik jari / Face ID.
class AppLockController extends StateNotifier<AppLockState> {
  AppLockController(
    this._biometric,
    this._settings, {
    DateTime Function()? clock,
  })  : _clock = clock ?? DateTime.now,
        super(const AppLockState()) {
    _ready = _load();
  }

  /// Kunci otomatis setelah aplikasi ditinggalkan selama 2 menit.
  static const autoLockAfter = Duration(minutes: 2);

  final BiometricAuth _biometric;
  final AppSettingsStorage _settings;
  final DateTime Function() _clock;

  late final Future<void> _ready;
  DateTime? _backgroundedAt;
  bool _startupHandled = false;

  /// Selesai setelah preferensi tersimpan selesai dibaca.
  Future<void> get ready => _ready;

  Future<void> _load() async {
    final enabled = await _settings.readBiometricLock();
    if (!mounted) return;
    state = state.copyWith(enabled: enabled);
  }

  Future<bool> biometricAvailable() => _biometric.isAvailable();

  /// Dipanggil sekali saat aplikasi dibuka dengan sesi aktif.
  Future<void> lockOnStart() async {
    await _ready;
    if (_startupHandled || !mounted) return;
    _startupHandled = true;
    if (state.enabled) {
      state = state.copyWith(status: AppLockStatus.locked);
    }
  }

  /// Mengaktifkan/menonaktifkan kunci biometrik lalu menyimpannya.
  Future<void> setEnabled(bool enabled) async {
    await _settings.writeBiometricLock(enabled);
    if (!mounted) return;
    state = state.copyWith(
      enabled: enabled,
      status: enabled ? state.status : AppLockStatus.unlocked,
      clearError: !enabled,
    );
  }

  /// Dipanggil saat aplikasi masuk ke background.
  void onBackgrounded() {
    _backgroundedAt ??= _clock();
  }

  /// Dipanggil saat aplikasi kembali aktif; kunci bila sudah melewati batas.
  Future<void> onResumed() async {
    final backgroundedAt = _backgroundedAt;
    _backgroundedAt = null;
    await _ready;
    if (!mounted || backgroundedAt == null || !state.enabled) return;
    if (state.isLocked) return;
    if (_clock().difference(backgroundedAt) >= autoLockAfter) {
      state = state.copyWith(status: AppLockStatus.locked);
    }
  }

  /// Membuka kunci dengan biometrik. Mengembalikan `true` bila berhasil.
  Future<bool> unlockWithBiometrics() async {
    if (!mounted) return false;
    if (!state.enabled) {
      state = state.copyWith(status: AppLockStatus.unlocked, clearError: true);
      return true;
    }

    state = state.copyWith(
      status: AppLockStatus.authenticating,
      clearError: true,
    );

    if (!await _biometric.isAvailable()) {
      if (!mounted) return false;
      state = state.copyWith(
        status: AppLockStatus.locked,
        error: 'Sensor biometrik tidak tersedia di perangkat ini.',
      );
      return false;
    }

    final success = await _biometric.authenticate(
      localizedReason: 'Pindai sidik jari atau Face ID untuk membuka E-SPP',
    );
    if (!mounted) return success;

    state = success
        ? state.copyWith(status: AppLockStatus.unlocked, clearError: true)
        : state.copyWith(
            status: AppLockStatus.locked,
            error: 'Verifikasi biometrik gagal. Silakan coba lagi.',
          );
    return success;
  }

  /// Membuka kunci paksa (mis. setelah logout) tanpa verifikasi.
  void unlock() {
    _startupHandled = true;
    state = state.copyWith(status: AppLockStatus.unlocked, clearError: true);
  }
}

final appSettingsStorageProvider =
    Provider<AppSettingsStorage>((ref) => AppSettingsStorage());

final biometricAuthProvider =
    Provider<BiometricAuth>((ref) => BiometricService());

final appLockControllerProvider =
    StateNotifierProvider<AppLockController, AppLockState>(
  (ref) => AppLockController(
    ref.read(biometricAuthProvider),
    ref.read(appSettingsStorageProvider),
  ),
);
