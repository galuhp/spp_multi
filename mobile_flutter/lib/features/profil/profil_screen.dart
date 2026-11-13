import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../../core/widgets/shad_app_bar.dart';
import '../../core/widgets/state_views.dart';
import '../auth/auth_controller.dart';
import '../dashboard/dashboard_providers.dart';

class ProfilScreen extends ConsumerWidget {
  const ProfilScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(dashboardProvider);
    final theme = ShadTheme.of(context);

    return Scaffold(
      backgroundColor: theme.colorScheme.background,
      appBar: const ShadAppBar(
        title: 'Kartu Pelajar & Profil',
        subtitle: 'Identitas resmi siswa dan pengaturan akun',
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          state.when(
            loading: () => const Column(
              children: [
                SkeletonBox(height: 280),
                SizedBox(height: 16),
                SkeletonBox(height: 140),
              ],
            ),
            error: (e, _) => ErrorView(
              message: e.toString(),
              onRetry: () => ref.invalidate(dashboardProvider),
            ),
            data: (d) => Column(
              children: [
                // Kartu Identitas Siswa Digital
                ShadCard(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Container(
                            width: 44,
                            height: 44,
                            decoration: BoxDecoration(
                              color: theme.colorScheme.primary
                                  .withValues(alpha: 0.12),
                              borderRadius: BorderRadius.circular(12),
                            ),
                            child: Icon(
                              LucideIcons.graduationCap,
                              color: theme.colorScheme.primary,
                              size: 24,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'KARTU PELAJAR DIGITAL',
                                  style: TextStyle(
                                    fontWeight: FontWeight.w800,
                                    fontSize: 12,
                                    letterSpacing: 0.8,
                                  ),
                                ),
                                Text(
                                  'SMK NEGERI CONTOH • AKREDITASI A',
                                  style: theme.textTheme.muted
                                      .copyWith(fontSize: 10),
                                ),
                              ],
                            ),
                          ),
                          const ShadBadge(child: Text('AKTIF')),
                        ],
                      ),
                      const SizedBox(height: 16),
                      const ShadSeparator.horizontal(),
                      const SizedBox(height: 16),

                      Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          CircleAvatar(
                            radius: 30,
                            backgroundColor: Colors.blue.shade100,
                            child: Text(
                              d.namaSiswa.isNotEmpty
                                  ? d.namaSiswa[0].toUpperCase()
                                  : 'S',
                              style: TextStyle(
                                color: Colors.blue.shade900,
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  d.namaSiswa,
                                  style: theme.textTheme.h4.copyWith(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 16,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  d.kelas,
                                  style: const TextStyle(
                                      fontWeight: FontWeight.w600,
                                      fontSize: 13),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  'NISN: ${d.nisn}',
                                  style: theme.textTheme.muted
                                      .copyWith(fontSize: 12),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),

                      // QR Code NISN
                      Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: ShadTheme.of(context).colorScheme.border,
                          ),
                        ),
                        child: QrImageView(
                          data: d.nisn.isEmpty ? '0065432190' : d.nisn,
                          size: 160,
                          semanticsLabel: 'QR code NISN ${d.nisn}',
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        'Pindai barcode ini pada loket kasir atau presensi sekolah',
                        style: theme.textTheme.muted.copyWith(fontSize: 11),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),

                // Kartu Keamanan & Status Aplikasi
                const ShadCard(
                  padding: EdgeInsets.all(16),
                  title: Text('Keamanan & Aplikasi',
                      style: TextStyle(fontWeight: FontWeight.bold)),
                  child: Column(
                    children: [
                      SizedBox(height: 10),
                      _SettingRow(
                        icon: LucideIcons.fingerprint,
                        title: 'Autentikasi Biometrik',
                        subtitle: 'Pindai sidik jari atau Face ID aktif',
                        trailing: ShadBadge.secondary(child: Text('Tersedia')),
                      ),
                      SizedBox(height: 8),
                      ShadSeparator.horizontal(),
                      SizedBox(height: 8),
                      _SettingRow(
                        icon: LucideIcons.bell,
                        title: 'Kanal Notifikasi SPP',
                        subtitle: 'Pengingat jatuh tempo tanggal 10',
                        trailing: ShadBadge.secondary(child: Text('Aktif')),
                      ),
                      SizedBox(height: 8),
                      ShadSeparator.horizontal(),
                      SizedBox(height: 8),
                      _SettingRow(
                        icon: LucideIcons.shieldCheck,
                        title: 'Enkripsi Token Sesi',
                        subtitle: 'JWT AES-256 Keychain/Keystore',
                        trailing: ShadBadge(child: Text('Aman')),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Logout Button with Dialog Confirmation
          ShadButton.destructive(
            onPressed: () => _confirmLogout(context, ref),
            leading: const Icon(LucideIcons.logOut, size: 16),
            child: const Text('Keluar dari Akun'),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }

  void _confirmLogout(BuildContext context, WidgetRef ref) {
    showShadDialog(
      context: context,
      builder: (ctx) => ShadDialog(
        title: const Text('Konfirmasi Keluar'),
        description:
            const Text('Apakah Anda yakin ingin keluar dari akun E-SPP ini?'),
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

class _SettingRow extends StatelessWidget {
  const _SettingRow({
    required this.icon,
    required this.title,
    required this.subtitle,
    required this.trailing,
  });

  final IconData icon;
  final String title;
  final String subtitle;
  final Widget trailing;

  @override
  Widget build(BuildContext context) => Row(
        children: [
          Icon(icon,
              size: 18,
              color: ShadTheme.of(context).colorScheme.mutedForeground),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: const TextStyle(
                        fontWeight: FontWeight.w600, fontSize: 13)),
                Text(
                  subtitle,
                  style: TextStyle(
                    fontSize: 11,
                    color: ShadTheme.of(context).colorScheme.mutedForeground,
                  ),
                ),
              ],
            ),
          ),
          trailing,
        ],
      );
}
