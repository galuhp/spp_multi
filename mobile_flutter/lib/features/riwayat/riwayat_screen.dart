import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:qr_flutter/qr_flutter.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../../core/constants/api_constants.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/shad_app_bar.dart';
import '../../core/widgets/state_views.dart';
import '../dashboard/dashboard_providers.dart';
import '../tagihan/tagihan_models.dart';
import '../tagihan/tagihan_providers.dart';
import 'receipt_pdf_service.dart';

class RiwayatScreen extends ConsumerWidget {
  const RiwayatScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(kartuSppProvider);
    final theme = ShadTheme.of(context);

    return Scaffold(
      backgroundColor: theme.colorScheme.background,
      appBar: const ShadAppBar(
        title: 'Riwayat Transaksi',
        subtitle: 'Catatan seluruh pembayaran yang telah diverifikasi',
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(kartuSppProvider.future),
        child: state.when(
          loading: () => ListView(
            padding: const EdgeInsets.all(16),
            children: const [
              SkeletonBox(height: 80),
              SizedBox(height: 10),
              SkeletonBox(height: 80),
              SizedBox(height: 10),
              SkeletonBox(height: 80),
            ],
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
              ? ListView(
                  children: const [
                    EmptyView(message: 'Belum ada transaksi pembayaran tersimpan.'),
                  ],
                )
              : ListView.separated(
                  padding: const EdgeInsets.all(16),
                  itemCount: kartu.pembayaran.length,
                  separatorBuilder: (_, __) => const SizedBox(height: 10),
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

    return Semantics(
      button: true,
      label:
          'Kuitansi ${item.kode}, ${Formatters.rupiah(item.jumlah)}${item.isVoid ? ', dibatalkan' : ''}',
      child: GestureDetector(
        onTap: () => context.push('/riwayat/kuitansi', extra: item),
        child: ShadCard(
          padding: const EdgeInsets.all(14),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: item.isVoid
                      ? theme.colorScheme.destructive.withValues(alpha: 0.12)
                      : Colors.blue.shade50,
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(
                  item.isVoid ? LucideIcons.fileX : LucideIcons.receipt,
                  color: item.isVoid
                      ? theme.colorScheme.destructive
                      : Colors.blue.shade800,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item.kode,
                      style: AppTheme.mono(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      '${Formatters.tanggal(item.tanggal)} • ${item.metode}',
                      style: theme.textTheme.muted.copyWith(fontSize: 12),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    Formatters.rupiah(item.jumlah),
                    style: TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                      decoration: item.isVoid ? TextDecoration.lineThrough : null,
                      fontFeatures: const [FontFeature.tabularFigures()],
                    ),
                  ),
                  const SizedBox(height: 2),
                  if (item.isVoid)
                    const ShadBadge.destructive(child: Text('BATAL'))
                  else
                    const ShadBadge(child: Text('LUNAS')),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Kuitansi digital dengan QR verifikasi keaslian berbasis Shadcn UI.
class KuitansiScreen extends ConsumerWidget {
  const KuitansiScreen({super.key, required this.item});

  final PembayaranItem item;

  String get _verifyUrl {
    final base = ApiConstants.baseUrl.replaceFirst('/api/v1', '');
    return '$base/bukti/${item.kode}/${item.tokenVerifikasi ?? ''}';
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = ShadTheme.of(context);
    final studentData = ref.watch(dashboardProvider).valueOrNull;
    final nama = studentData?.namaSiswa ?? 'M. Rizky Pratama';
    final kelas = studentData?.kelas ?? 'XII RPL 1';
    final nisn = studentData?.nisn ?? '0065432190';

    return Scaffold(
      backgroundColor: theme.colorScheme.background,
      appBar: const ShadAppBar(title: 'Bukti Kuitansi Pembayaran'),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Slip Kuitansi Resmi
          ShadCard(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Kop Bukti
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'E-SPP SEKOLAH',
                          style: TextStyle(
                            fontWeight: FontWeight.w800,
                            letterSpacing: 1.2,
                            fontSize: 13,
                          ),
                        ),
                        Text(
                          'Kuitansi Pembayaran Digital',
                          style: theme.textTheme.muted.copyWith(fontSize: 12),
                        ),
                      ],
                    ),
                    if (item.isVoid)
                      const ShadBadge.destructive(child: Text('DIBATALKAN'))
                    else
                      const ShadBadge(child: Text('TERVERIFIKASI')),
                  ],
                ),
                const SizedBox(height: 16),
                const ShadSeparator.horizontal(),
                const SizedBox(height: 16),

                // Detail Data
                _KuitansiRow(label: 'No. Transaksi', value: item.kode, isMono: true),
                _KuitansiRow(label: 'Waktu Transaksi', value: Formatters.tanggal(item.tanggal)),
                _KuitansiRow(label: 'Nama Siswa', value: nama),
                _KuitansiRow(label: 'NISN / Kelas', value: '$nisn / $kelas'),
                _KuitansiRow(label: 'Metode Bayar', value: item.metode),
                _KuitansiRow(label: 'Petugas Loket', value: item.petugas),

                const SizedBox(height: 16),
                const ShadSeparator.horizontal(),
                const SizedBox(height: 16),

                // Total Box
                Container(
                  padding: const EdgeInsets.all(14),
                  decoration: BoxDecoration(
                    color: theme.colorScheme.card,
                    borderRadius: BorderRadius.circular(10),
                    border: Border.all(color: theme.colorScheme.border),
                  ),
                  child: Column(
                    children: [
                      const Text(
                        'TOTAL PEMBAYARAN',
                        style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.grey),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        Formatters.rupiah(item.jumlah),
                        style: TextStyle(
                          fontSize: 26,
                          fontWeight: FontWeight.w800,
                          color: item.isVoid
                              ? theme.colorScheme.destructive
                              : AppColors.paid(Theme.of(context).brightness),
                          fontFeatures: const [FontFeature.tabularFigures()],
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        'Terbilang: ${ReceiptPdfService.terbilang(item.jumlah)}',
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 11,
                          fontStyle: FontStyle.italic,
                          color: Colors.grey,
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 20),
                // QR Verification
                Center(
                  child: Column(
                    children: [
                      Container(
                        color: Colors.white,
                        padding: const EdgeInsets.all(8),
                        child: QrImageView(
                          data: _verifyUrl,
                          size: 140,
                          semanticsLabel: 'QR verifikasi kuitansi ${item.kode}',
                        ),
                      ),
                      const SizedBox(height: 6),
                      Text(
                        'Pindai QR untuk verifikasi keaslian kuitansi',
                        style: theme.textTheme.muted.copyWith(fontSize: 11),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Actions
          ShadButton(
            onPressed: () => ReceiptPdfService.printReceipt(
              item: item,
              namaSiswa: nama,
              kelas: kelas,
              nisn: nisn,
            ),
            leading: const Icon(LucideIcons.printer, size: 16),
            child: const Text('Unduh / Cetak Dokumen PDF'),
          ),
          const SizedBox(height: 10),
          ShadButton.outline(
            onPressed: () => ReceiptPdfService.shareReceiptPdf(
              item: item,
              namaSiswa: nama,
              kelas: kelas,
              nisn: nisn,
            ),
            leading: const Icon(LucideIcons.share2, size: 16),
            child: const Text('Bagikan Kuitansi Digital'),
          ),
          const SizedBox(height: 24),
        ],
      ),
    );
  }
}

class _KuitansiRow extends StatelessWidget {
  const _KuitansiRow({
    required this.label,
    required this.value,
    this.isMono = false,
  });

  final String label;
  final String value;
  final bool isMono;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
            Text(
              value,
              style: isMono
                  ? AppTheme.mono(fontWeight: FontWeight.w600, fontSize: 13)
                  : const TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
            ),
          ],
        ),
      );
}
