import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../security/app_lock_controller.dart';
import '../theme/app_theme.dart';
import '../../features/auth/auth_controller.dart';

/// Membungkus aplikasi dan menampilkan layar kunci saat kunci biometrik aktif.
class AppLockGate extends ConsumerStatefulWidget {
  const AppLockGate({super.key, required this.child});

  final Widget child;

  @override
  ConsumerState<AppLockGate> createState() => _AppLockGateState();
}

class _AppLockGateState extends ConsumerState<AppLockGate>
    with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      if (ref.read(authControllerProvider) == AuthStatus.signedIn) {
        ref.read(appLockControllerProvider.notifier).lockOnStart();
      }
    });
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    final controller = ref.read(appLockControllerProvider.notifier);
    switch (state) {
      case AppLifecycleState.paused:
      case AppLifecycleState.hidden:
        controller.onBackgrounded();
      case AppLifecycleState.resumed:
        controller.onResumed();
      case AppLifecycleState.inactive:
      case AppLifecycleState.detached:
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    ref.listen<AuthStatus>(authControllerProvider, (previous, next) {
      final controller = ref.read(appLockControllerProvider.notifier);
      if (next == AuthStatus.signedIn && previous != AuthStatus.signedIn) {
        controller.lockOnStart();
      } else if (next == AuthStatus.signedOut) {
        controller.unlock();
      }
    });

    final lock = ref.watch(appLockControllerProvider);

    return Stack(
      children: [
        widget.child,
        if (lock.isLocked)
          Positioned.fill(
            child: _LockScreen(
              authenticating: lock.status == AppLockStatus.authenticating,
              error: lock.error,
            ),
          ),
      ],
    );
  }
}

class _LockScreen extends ConsumerWidget {
  const _LockScreen({required this.authenticating, this.error});

  final bool authenticating;
  final String? error;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = ShadTheme.of(context);
    return ColoredBox(
      color: theme.colorScheme.background,
      child: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 380),
              child: ShadCard(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Container(
                      width: 64,
                      height: 64,
                      alignment: Alignment.center,
                      decoration: const BoxDecoration(
                        shape: BoxShape.circle,
                        gradient: LinearGradient(
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                          colors: [AppColors.headerStart, AppColors.headerEnd],
                        ),
                      ),
                      child: const Icon(
                        LucideIcons.lockKeyhole,
                        color: AppColors.onHeader,
                        size: 28,
                      ),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Aplikasi Terkunci',
                      style: theme.textTheme.h3,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Verifikasi identitas Anda untuk melanjutkan.',
                      textAlign: TextAlign.center,
                      style: theme.textTheme.muted,
                    ),
                    if (error != null) ...[
                      const SizedBox(height: 12),
                      Text(
                        error!,
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          color: theme.colorScheme.destructive,
                          fontSize: 13,
                        ),
                      ),
                    ],
                    const SizedBox(height: 20),
                    FutureBuilder<bool>(
                      future: ref
                          .read(appLockControllerProvider.notifier)
                          .biometricAvailable(),
                      builder: (context, snapshot) {
                        final available = snapshot.data ?? false;
                        if (snapshot.connectionState != ConnectionState.done) {
                          return const SizedBox(height: 44);
                        }
                        if (!available) {
                          return Text(
                            'Sensor biometrik tidak tersedia di perangkat ini.',
                            textAlign: TextAlign.center,
                            style: theme.textTheme.muted,
                          );
                        }
                        return ShadButton(
                          width: double.infinity,
                          onPressed: authenticating
                              ? null
                              : () => ref
                                  .read(appLockControllerProvider.notifier)
                                  .unlockWithBiometrics(),
                          leading: authenticating
                              ? const Icon(LucideIcons.loaderCircle, size: 16)
                                  .animate(
                                    onPlay: (controller) => controller.repeat(),
                                  )
                                  .rotate(
                                    duration: const Duration(milliseconds: 900),
                                  )
                              : const Icon(LucideIcons.fingerprint, size: 16),
                          child: Text(
                            authenticating
                                ? 'Memverifikasi…'
                                : 'Buka dengan Biometrik',
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 8),
                    ShadButton.ghost(
                      width: double.infinity,
                      onPressed: () async {
                        await ref
                            .read(authControllerProvider.notifier)
                            .logout();
                        ref.read(appLockControllerProvider.notifier).unlock();
                      },
                      leading: const Icon(LucideIcons.logOut, size: 16),
                      child: const Text('Keluar dari Akun'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
