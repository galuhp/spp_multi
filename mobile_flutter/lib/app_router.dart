import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'core/widgets/shad_bottom_nav.dart';
import 'features/auth/auth_controller.dart';
import 'features/auth/login_screen.dart';
import 'features/dashboard/dashboard_screen.dart';
import 'features/profil/profil_screen.dart';
import 'features/riwayat/riwayat_screen.dart';
import 'features/tagihan/tagihan_models.dart';
import 'features/tagihan/tagihan_screen.dart';
import 'features/wali/wali_screen.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

class _HomeDispatcher extends ConsumerWidget {
  const _HomeDispatcher();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final role = ref.watch(userRoleProvider).asData?.value;
    if (role == 'wali_kelas') {
      return const WaliScreen();
    }
    return const DashboardScreen();
  }
}

class _Shell extends ConsumerWidget {
  const _Shell(this.shell);
  final StatefulNavigationShell shell;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final role = ref.watch(userRoleProvider).asData?.value;
    final isWali = role == 'wali_kelas';

    return Scaffold(
      body: shell,
      bottomNavigationBar: ShadBottomNav(
        currentIndex: shell.currentIndex,
        onTap: (i) =>
            shell.goBranch(i, initialLocation: i == shell.currentIndex),
        items: isWali
            ? const [
                ShadBottomNavItem(
                  icon: LucideIcons.graduationCap,
                  activeIcon: LucideIcons.graduationCap,
                  label: 'Perwalian',
                ),
                ShadBottomNavItem(
                  icon: LucideIcons.receiptText,
                  activeIcon: LucideIcons.receiptText,
                  label: 'Tagihan',
                ),
                ShadBottomNavItem(
                  icon: LucideIcons.history,
                  activeIcon: LucideIcons.history,
                  label: 'Riwayat',
                ),
                ShadBottomNavItem(
                  icon: LucideIcons.user,
                  activeIcon: LucideIcons.user,
                  label: 'Profil',
                ),
              ]
            : const [
                ShadBottomNavItem(
                  icon: LucideIcons.house,
                  activeIcon: LucideIcons.house,
                  label: 'Beranda',
                ),
                ShadBottomNavItem(
                  icon: LucideIcons.receiptText,
                  activeIcon: LucideIcons.receiptText,
                  label: 'Tagihan',
                ),
                ShadBottomNavItem(
                  icon: LucideIcons.history,
                  activeIcon: LucideIcons.history,
                  label: 'Riwayat',
                ),
                ShadBottomNavItem(
                  icon: LucideIcons.idCard,
                  activeIcon: LucideIcons.idCard,
                  label: 'Profil',
                ),
              ],
      ),
    );
  }
}

final routerProvider = Provider<GoRouter>((ref) {
  final refresh = ValueNotifier<int>(0);
  ref.listen(authControllerProvider, (_, __) => refresh.value++);

  return GoRouter(
    initialLocation: '/',
    refreshListenable: refresh,
    redirect: (context, state) {
      final status = ref.read(authControllerProvider);
      if (status == AuthStatus.unknown) return null;
      final atLogin = state.matchedLocation == '/login';
      if (status == AuthStatus.signedOut) return atLogin ? null : '/login';
      return atLogin ? '/' : null;
    },
    routes: [
      GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
      GoRoute(
        path: '/wali',
        builder: (_, __) => const WaliScreen(),
      ),
      GoRoute(
        path: '/riwayat/kuitansi',
        builder: (_, state) =>
            KuitansiScreen(item: state.extra! as PembayaranItem),
      ),
      StatefulShellRoute.indexedStack(
        builder: (_, __, shell) => _Shell(shell),
        branches: [
          StatefulShellBranch(routes: [
            GoRoute(path: '/', builder: (_, __) => const _HomeDispatcher()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/tagihan', builder: (_, __) => const TagihanScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/riwayat', builder: (_, __) => const RiwayatScreen()),
          ]),
          StatefulShellBranch(routes: [
            GoRoute(path: '/profil', builder: (_, __) => const ProfilScreen()),
          ]),
        ],
      ),
    ],
  );
});
