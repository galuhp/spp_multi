import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/state_views.dart';
import '../auth/auth_controller.dart';
import '../tagihan/tagihan_models.dart';
import 'dashboard_models.dart';
import 'dashboard_providers.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(dashboardProvider);
    return Scaffold(
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(dashboardProvider.future),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: EdgeInsets.zero,
          children: [
            state.when(
              loading: () => Padding(
                padding: EdgeInsets.fromLTRB(
                    16, MediaQuery.paddingOf(context).top + 16, 16, 16),
                child: const SkeletonBox(height: 220),
              ),
              error: (e, _) => Padding(
                padding: EdgeInsets.fromLTRB(
                    16, MediaQuery.paddingOf(context).top + 16, 16, 16),
                child: ErrorView(
                  message: e.toString(),
                  onRetry: () => ref.invalidate(dashboardProvider),
                ),
              ),
              data: (d) => _DashboardContent(
                data: d,
                onLogout: () =>
                    ref.read(authControllerProvider.notifier).logout(),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

String _initials(String name) {
  final parts = name.trim().split(RegExp(r'\s+'));
  final letters = parts.take(2).map((p) => p.isEmpty ? '' : p[0]).join();
  return letters.isEmpty ? '?' : letters.toUpperCase();
}

class _DashboardContent extends StatelessWidget {
  const _DashboardContent({required this.data, required this.onLogout});

  final RingkasanKeuangan data;
  final VoidCallback onLogout;

  @override
  Widget build(BuildContext context) {
    final theme = ShadTheme.of(context);
    final lunas = data.tunggakanJatuhTempo == 0;
    final brightness = Theme.of(context).brightness;
    final status = lunas ? StatusTagihan.lunas : StatusTagihan.belum;
    final color = status.color(brightness);
    final top = MediaQuery.paddingOf(context).top;

    return Stack(
      children: [
        Container(
          height: top + 150,
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [AppColors.headerStart, AppColors.headerEnd],
            ),
          ),
          padding: EdgeInsets.fromLTRB(16, top + 12, 8, 0),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ExcludeSemantics(
                child: Container(
                  width: 48,
                  height: 48,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white.withValues(alpha: 0.2),
                    border: Border.all(color: AppColors.onHeader, width: 1.5),
                  ),
                  child: Text(
                    _initials(data.namaSiswa),
                    style: const TextStyle(
                      color: AppColors.onHeader,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Halo,',
                        style: TextStyle(color: AppColors.onHeaderMuted)),
                    Text(
                      data.namaSiswa,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: theme.textTheme.h4.copyWith(color: AppColors.onHeader),
                    ),
                    Text(data.kelas,
                        style:
                            const TextStyle(color: AppColors.onHeaderMuted)),
                  ],
                ),
              ),
              IconButton(
                tooltip: 'Keluar',
                icon: const Icon(LucideIcons.logOut,
                    size: 20, color: AppColors.onHeader),
                onPressed: onLogout,
              ),
            ],
          ),
        ),
        Padding(
          padding: EdgeInsets.fromLTRB(16, top + 120, 16, 24),
          child: ShadCard(
            backgroundColor: status.background(brightness),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 4),
                const Text('Tunggakan Jatuh Tempo'),
                const SizedBox(height: 4),
                Text(
                  Formatters.rupiah(data.tunggakanJatuhTempo),
                  style: TextStyle(
                    fontSize: 28,
                    fontWeight: FontWeight.w800,
                    color: color,
                    fontFeatures: const [FontFeature.tabularFigures()],
                  ),
                ),
                const SizedBox(height: 12),
                ShadBadge(
                  backgroundColor: color,
                  foregroundColor: brightness == Brightness.dark
                      ? const Color(0xFF0B1220)
                      : Colors.white,
                  child: Text(lunas ? 'Tidak ada tunggakan' : 'Perlu dibayar'),
                ),
                const SizedBox(height: 16),
                ShadProgress(value: data.progress, color: color),
                const SizedBox(height: 8),
                Text(
                  '${Formatters.rupiah(data.totalDibayar)} dari ${Formatters.rupiah(data.totalTagihan)}',
                  style: theme.textTheme.small,
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
