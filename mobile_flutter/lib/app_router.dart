import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'features/auth/auth_controller.dart';
import 'features/auth/login_screen.dart';
import 'features/dashboard/dashboard_screen.dart';
import 'features/profil/profil_screen.dart';
import 'features/riwayat/riwayat_screen.dart';
import 'features/tagihan/tagihan_models.dart';
import 'features/tagihan/tagihan_screen.dart';

class _Shell extends StatelessWidget {
  const _Shell(this.shell);
  final StatefulNavigationShell shell;

  @override
  Widget build(BuildContext context) => Scaffold(
        body: shell,
        bottomNavigationBar: NavigationBar(
          selectedIndex: shell.currentIndex,
          onDestinationSelected: (i) =>
              shell.goBranch(i, initialLocation: i == shell.currentIndex),
          destinations: const [
            NavigationDestination(icon: Icon(Icons.home_outlined), label: 'Beranda'),
            NavigationDestination(icon: Icon(Icons.receipt_long_outlined), label: 'Tagihan'),
            NavigationDestination(icon: Icon(Icons.history), label: 'Riwayat'),
            NavigationDestination(icon: Icon(Icons.badge_outlined), label: 'Profil'),
          ],
        ),
      );
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
        path: '/riwayat/kuitansi',
        builder: (_, state) => KuitansiScreen(item: state.extra! as PembayaranItem),
      ),
      StatefulShellRoute.indexedStack(
        builder: (_, __, shell) => _Shell(shell),
        branches: [
          StatefulShellBranch(routes: [
            GoRoute(path: '/', builder: (_, __) => const DashboardScreen()),
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
