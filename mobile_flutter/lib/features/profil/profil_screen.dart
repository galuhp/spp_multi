import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../../core/widgets/state_views.dart';
import '../auth/auth_controller.dart';
import '../dashboard/dashboard_providers.dart';

class ProfilScreen extends ConsumerWidget {
  const ProfilScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(dashboardProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Kartu Pelajar')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          state.when(
            loading: () => const SkeletonBox(height: 280),
            error: (e, _) => ErrorView(
              message: e.toString(),
              onRetry: () => ref.invalidate(dashboardProvider),
            ),
            data: (d) => ShadCard(
              title: Text(d.namaSiswa),
              description: Text('${d.kelas}\nNISN ${d.nisn}'),
              child: Padding(
                padding: const EdgeInsets.symmetric(vertical: 16),
                child: Center(
                  child: d.nisn.isEmpty
                      ? const Text('NISN belum tersedia')
                      : Container(
                          color: Colors.white,
                          padding: const EdgeInsets.all(12),
                          child: QrImageView(
                              data: d.nisn,
                              size: 200,
                              semanticsLabel: 'QR code NISN ${d.nisn}',
                            ),
                        ),
                ),
              ),
            ),
          ),
          const SizedBox(height: 16),
          ShadButton.destructive(
            onPressed: () => ref.read(authControllerProvider.notifier).logout(),
            leading: const Icon(LucideIcons.logOut, size: 16),
            child: const Text('Keluar'),
          ),
        ],
      ),
    );
  }
}
