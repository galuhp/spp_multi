import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../../core/notifications/notification_service.dart';
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
    final theme = ShadTheme.of(context);

    return Scaffold(
      backgroundColor: theme.colorScheme.background,
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(dashboardProvider.future),
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: EdgeInsets.zero,
          children: [
            state.when(
              loading: () => Padding(
                padding: EdgeInsets.fromLTRB(
                  16,
                  MediaQuery.paddingOf(context).top + 16,
                  16,
                  16,
                ),
                child: const Column(
                  children: [
                    SkeletonBox(height: 200),
                    SizedBox(height: 16),
                    SkeletonBox(height: 140),
                    SizedBox(height: 16),
                    SkeletonBox(height: 160),
                  ],
                ),
              ),
              error: (e, _) => Padding(
                padding: EdgeInsets.fromLTRB(
                  16,
                  MediaQuery.paddingOf(context).top + 16,
                  16,
                  16,
                ),
                child: ErrorView(
                  message: e.toString(),
                  onRetry: () => ref.invalidate(dashboardProvider),
                ),
              ),
              data: (d) => _DashboardContent(
                data: d,
                onLogout: () => _confirmLogout(context, ref),
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _confirmLogout(BuildContext context, WidgetRef ref) {
    showShadDialog(
      context: context,
      builder: (ctx) => ShadDialog(
        title: const Text('Konfirmasi Keluar'),
        description: const Text('Apakah Anda yakin ingin keluar dari akun E-SPP?'),
        actions: [
          ShadButton.outline(
            child: const Text('Batal'),
            onPressed: () => Navigator.of(ctx).pop(),
          ),
          ShadButton.destructive(
            child: const Text('Ya, Keluar'),
            onPressed: () {
              Navigator.of(ctx).pop();
              ref.read(authControllerProvider.notifier).logout();
            },
          ),
        ],
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

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        // Top Header Gradasi Navy
        Container(
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [AppColors.headerStart, AppColors.headerEnd],
            ),
          ),
          padding: EdgeInsets.fromLTRB(16, top + 12, 16, 20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.center,
                children: [
                  Container(
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
                        fontSize: 16,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Portal Siswa E-SPP',
                          style: TextStyle(
                            color: AppColors.onHeaderMuted,
                            fontSize: 12,
                          ),
                        ),
                        Text(
                          data.namaSiswa,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.h4.copyWith(
                            color: AppColors.onHeader,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          '${data.kelas} • NISN ${data.nisn}',
                          style: const TextStyle(
                            color: AppColors.onHeaderMuted,
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    tooltip: 'Uji Pengingat Notifikasi',
                    icon: const Icon(LucideIcons.bellRing, color: AppColors.onHeader),
                    onPressed: () async {
                      await NotificationService().showNotification(
                        id: 101,
                        title: 'Pengingat Pembayaran SPP',
                        body: lunas
                            ? 'Seluruh kewajiban SPP Anda telah lunas. Terima kasih!'
                            : 'Tagihan SPP sebesar Rp ${Rupiah.format(data.tunggakanJatuhTempo)} jatuh tempo tanggal 10.',
                      );
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Notifikasi lokal SPP berhasil dipicu.'),
                            duration: Duration(seconds: 2),
                          ),
                        );
                      }
                    },
                  ),
                  IconButton(
                    tooltip: 'Keluar',
                    icon: const Icon(LucideIcons.logOut, color: AppColors.onHeader),
                    onPressed: onLogout,
                  ),
                ],
              ),
              const SizedBox(height: 20),

              // Kartu Hero Tunggakan Utama
              ShadCard(
                backgroundColor: status.background(brightness),
                padding: const EdgeInsets.all(18),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Tunggakan Jatuh Tempo',
                          style: TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        if (lunas)
                          const ShadBadge(
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(LucideIcons.checkCircle2, size: 12),
                                SizedBox(width: 4),
                                Text('Semua Lunas'),
                              ],
                            ),
                          )
                        else
                          const ShadBadge.destructive(
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Icon(LucideIcons.alertCircle, size: 12),
                                SizedBox(width: 4),
                                Text('Perlu Diselesaikan'),
                              ],
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      Formatters.rupiah(data.tunggakanJatuhTempo),
                      style: TextStyle(
                        fontSize: 30,
                        fontWeight: FontWeight.w800,
                        color: color,
                        fontFeatures: const [FontFeature.tabularFigures()],
                      ),
                    ),
                    const SizedBox(height: 14),
                    ShadProgress(value: data.progress, color: color),
                    const SizedBox(height: 8),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Terbayar: ${Formatters.rupiah(data.totalDibayar)}',
                          style: theme.textTheme.small.copyWith(color: Colors.grey.shade700),
                        ),
                        Text(
                          'Total: ${Formatters.rupiah(data.totalTagihan)}',
                          style: theme.textTheme.small.copyWith(fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),

        // Quick Actions Section
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 20, 16, 8),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Menu Utama & Layanan',
                style: theme.textTheme.large.copyWith(fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _QuickActionCard(
                      icon: LucideIcons.calendarCheck,
                      title: 'Kartu SPP',
                      subtitle: '12 Bulan',
                      color: Colors.blue.shade700,
                      onTap: () => context.go('/tagihan'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _QuickActionCard(
                      icon: LucideIcons.receipt,
                      title: 'Riwayat Bayar',
                      subtitle: 'Bukti Kuitansi',
                      color: AppColors.success,
                      onTap: () => context.go('/riwayat'),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Expanded(
                    child: _QuickActionCard(
                      icon: LucideIcons.idCard,
                      title: 'Kartu Pelajar',
                      subtitle: 'Barcode NISN',
                      color: Colors.indigo.shade700,
                      onTap: () => context.go('/profil'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _QuickActionCard(
                      icon: LucideIcons.bellRing,
                      title: 'Pengingat',
                      subtitle: 'Jadwal Tgl 10',
                      color: Colors.amber.shade800,
                      onTap: () async {
                        await NotificationService().scheduleMonthlyReminder(
                          bulan: 'Bulan Berjalan',
                          nominal: data.tunggakanJatuhTempo,
                        );
                        if (context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(
                              content: Text('Jadwal pengingat SPP aktif untuk tanggal 10.'),
                            ),
                          );
                        }
                      },
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),

        // Information Callout Alert
        const Padding(
          padding: EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: ShadAlert(
            icon: Icon(LucideIcons.info, size: 18),
            title: Text('Informasi Pembayaran'),
            description: Text(
              'Pembayaran SPP jatuh tempo pada tanggal 10 setiap bulannya. Pembayaran dapat dilakukan via kasir sekolah atau transfer bank.',
            ),
          ),
        ),
        const SizedBox(height: 24),
      ],
    );
  }
}

class _QuickActionCard extends StatelessWidget {
  const _QuickActionCard({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.color,
    required this.onTap,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Semantics(
      button: true,
      label: '$title, $subtitle',
      child: GestureDetector(
        onTap: onTap,
        child: ShadCard(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              Container(
                width: 40,
                height: 40,
                decoration: BoxDecoration(
                  color: color.withValues(alpha: 0.12),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: color, size: 20),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 11, color: Colors.grey),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
