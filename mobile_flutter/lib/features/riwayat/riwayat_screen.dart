import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:share_plus/share_plus.dart';
import 'receipt_pdf_service.dart';
import '../dashboard/dashboard_providers.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../../core/constants/api_constants.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/state_views.dart';
import '../tagihan/tagihan_models.dart';
import '../tagihan/tagihan_providers.dart';

class RiwayatScreen extends ConsumerWidget {
  const RiwayatScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(kartuSppProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Riwayat Pembayaran')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(kartuSppProvider.future),
        child: state.when(
          loading: () => ListView(
            padding: const EdgeInsets.all(16),
            children: const [SkeletonBox(height: 80), SizedBox(height: 8), SkeletonBox(height: 80)],
          ),
          error: (e, _) => ListView(
            padding: const EdgeInsets.all(16),
            children: [
              ErrorView(
                message: e.toString(),
                onRetry: () => ref.invalidate(kartuSppProvider),
              ),
            ],
          ),
          data: (kartu) => kartu.pembayaran.isEmpty
              ? ListView(children: const [EmptyView(message: 'Belum ada transaksi pembayaran.')])
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: kartu.pembayaran.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 8),
                  itemBuilder: (_, i) => _PembayaranTile(item: kartu.pembayaran[i]),
                ),
        ),
      ),
    );
  }
}

class _PembayaranTile extends StatelessWidget {
  const _PembayaranTile({required this.item});

  final PembayaranItem item;

  @override
  Widget build(BuildContext context) {
    final theme = ShadTheme.of(context);
    final radius = BorderRadius.circular(14);
    return Semantics(
      button: true,
      label:
          'Kuitansi ${item.kode}, ${Formatters.rupiah(item.jumlah)}${item.isVoid ? ', dibatalkan' : ''}',
      child: Material(
        color: theme.colorScheme.card,
        shape: RoundedRectangleBorder(
          borderRadius: radius,
          side: BorderSide(color: theme.colorScheme.border),
        ),
        child: InkWell(
          customBorder: RoundedRectangleBorder(borderRadius: radius),
          onTap: () => context.push('/riwayat/kuitansi', extra: item),
          child: ConstrainedBox(
            constraints: const BoxConstraints(minHeight: 64),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: ExcludeSemantics(
                child: Row(
                  children: [
                    const Icon(LucideIcons.receipt),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(item.kode,
                              style: AppTheme.mono(fontWeight: FontWeight.w600)),
                          Text(
                              '${Formatters.tanggal(item.tanggal)} • ${item.metode}',
                              style: theme.textTheme.muted),
                        ],
                      ),
                    ),
                    Text(
                      Formatters.rupiah(item.jumlah),
                      style: TextStyle(
                        fontWeight: FontWeight.w700,
                        decoration:
                            item.isVoid ? TextDecoration.lineThrough : null,
                        fontFeatures: const [FontFeature.tabularFigures()],
                      ),
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

/// Kuitansi digital dengan QR verifikasi keaslian.
class KuitansiScreen extends ConsumerWidget {
  const KuitansiScreen({super.key, required this.item});

  final PembayaranItem item;

  String get _verifyUrl {
    final base = ApiConstants.baseUrl.replaceFirst('/api/v1', '');
    return '$base/bukti/${item.kode}/${item.tokenVerifikasi ?? ''}';
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final studentData = ref.watch(dashboardProvider).valueOrNull;
    final nama = studentData?.namaSiswa ?? 'Siswa E-SPP';
    final kelas = studentData?.kelas ?? '-';
    final nisn = studentData?.nisn ?? '-';

    return Scaffold(
      appBar: AppBar(title: const Text('Kuitansi Digital')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          ShadCard(
            title: Text(item.kode, style: AppTheme.mono()),
            description: Text(Formatters.tanggal(item.tanggal)),
            trailing: item.isVoid
                ? const ShadBadge.destructive(child: Text('VOID'))
                : const ShadBadge(child: Text('VALID')),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 16),
                Text('Jumlah: ${Formatters.rupiah(item.jumlah)}'),
                Text('Metode: ${item.metode}'),
                Text('Petugas: ${item.petugas}'),
                const SizedBox(height: 16),
                Center(
                  child: QrImageView(
                    data: _verifyUrl,
                    size: 180,
                    backgroundColor: Colors.white,
                    semanticsLabel: 'QR code verifikasi kuitansi ${item.kode}',
                  ),
                ),
                const SizedBox(height: 20),
                ShadButton(
                  width: double.infinity,
                  onPressed: () => ReceiptPdfService.printReceipt(
                    item: item,
                    namaSiswa: nama,
                    kelas: kelas,
                    nisn: nisn,
                  ),
                  leading: const ExcludeSemantics(
                    child: Icon(LucideIcons.printer, size: 16),
                  ),
                  child: const Text('Unduh / Cetak PDF'),
                ),
                const SizedBox(height: 8),
                ShadButton.outline(
                  width: double.infinity,
                  onPressed: () => ReceiptPdfService.shareReceiptPdf(
                    item: item,
                    namaSiswa: nama,
                    kelas: kelas,
                    nisn: nisn,
                  ),
                  leading: const ExcludeSemantics(
                    child: Icon(LucideIcons.fileText, size: 16),
                  ),
                  child: const Text('Kirim Berkas PDF'),
                ),
                const SizedBox(height: 8),
                ShadButton.ghost(
                  width: double.infinity,
                  onPressed: () => Share.share(
                    'Bukti pembayaran SPP ${item.kode}: $_verifyUrl',
                  ),
                  leading: const ExcludeSemantics(
                    child: Icon(LucideIcons.share2, size: 16),
                  ),
                  child: const Text('Bagikan Tautan ke WhatsApp'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
