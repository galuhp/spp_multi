import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/shad_app_bar.dart';
import '../../core/widgets/state_views.dart';
import 'tagihan_models.dart';
import 'tagihan_providers.dart';

class TagihanScreen extends ConsumerWidget {
  const TagihanScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(kartuSppProvider);
    final theme = ShadTheme.of(context);

    return Scaffold(
      backgroundColor: theme.colorScheme.background,
      appBar: const ShadAppBar(
        title: 'Kartu Tagihan Siswa',
        subtitle: 'Tahun Ajaran 2026/2027',
      ),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(kartuSppProvider.future),
        child: state.when(
          loading: () => ListView(
            padding: const EdgeInsets.all(16),
            children: const [
              SkeletonBox(height: 120),
              SizedBox(height: 12),
              SkeletonBox(height: 300),
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
          data: (kartu) => ListView(
            padding: const EdgeInsets.all(16),
            children: [
              ShadTabs<String>(
                value: 'spp',
                tabs: [
                  ShadTab(
                    value: 'spp',
                    content: _SppGrid(items: kartu.spp),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(LucideIcons.calendar, size: 14),
                        SizedBox(width: 6),
                        Text('SPP Bulanan'),
                      ],
                    ),
                  ),
                  ShadTab(
                    value: 'non',
                    content: _NonSppList(items: kartu.nonSpp),
                    child: const Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(LucideIcons.layers, size: 14),
                        SizedBox(width: 6),
                        Text('Pos Non-SPP'),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SppGrid extends StatelessWidget {
  const _SppGrid({required this.items});

  final List<TagihanBulanItem> items;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return const EmptyView(message: 'Belum ada tagihan SPP.');
    }

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 12),
        // Legend Status Bar
        Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            color: ShadTheme.of(context).colorScheme.card,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: ShadTheme.of(context).colorScheme.border),
          ),
          child: const Row(
            mainAxisAlignment: MainAxisAlignment.spaceAround,
            children: [
              _StatusLegend(label: 'Lunas', color: AppColors.success),
              _StatusLegend(label: 'Belum Lunas', color: AppColors.danger),
              _StatusLegend(label: 'Mendatang', color: AppColors.neutral),
            ],
          ),
        ),
        const SizedBox(height: 14),

        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: items.length,
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 3,
            mainAxisSpacing: 10,
            crossAxisSpacing: 10,
            mainAxisExtent: 100,
          ),
          itemBuilder: (_, i) => _BulanCell(item: items[i]),
        ),
        const SizedBox(height: 20),
      ],
    );
  }
}

class _StatusLegend extends StatelessWidget {
  const _StatusLegend({required this.label, required this.color});

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) => Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 8,
            height: 8,
            decoration: BoxDecoration(shape: BoxShape.circle, color: color),
          ),
          const SizedBox(width: 6),
          Text(label, style: const TextStyle(fontSize: 11, color: Colors.grey)),
        ],
      );
}

class _BulanCell extends StatelessWidget {
  const _BulanCell({required this.item});

  final TagihanBulanItem item;

  @override
  Widget build(BuildContext context) {
    final b = Theme.of(context).brightness;
    final s = item.status;
    final fg = s.color(b);

    return Semantics(
      button: true,
      label: '${item.bulan} ${item.tahun}, status ${s.label}, nominal ${Formatters.rupiah(item.nominal)}',
      child: GestureDetector(
        onTap: () => _showDetail(context),
        child: Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: s.background(b),
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: fg.withValues(alpha: 0.35), width: 1.2),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                item.bulan,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 13,
                  color: fg,
                ),
              ),
              const SizedBox(height: 4),
              Icon(_iconFor(s), size: 16, color: fg),
              const SizedBox(height: 4),
              Text(
                s.label,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(fontSize: 11, color: fg, fontWeight: FontWeight.w500),
              ),
            ],
          ),
        ),
      ),
    );
  }

  static IconData _iconFor(StatusTagihan s) => switch (s) {
        StatusTagihan.lunas => LucideIcons.circleCheck,
        StatusTagihan.sebagian => LucideIcons.circleDot,
        StatusTagihan.belum => LucideIcons.circleAlert,
        StatusTagihan.akanDatang => LucideIcons.clock,
        StatusTagihan.ditanggung => LucideIcons.shieldCheck,
      };

  void _showDetail(BuildContext context) {
    final b = Theme.of(context).brightness;
    showShadSheet(
      context: context,
      side: ShadSheetSide.bottom,
      builder: (_) => ShadSheet(
        title: Text('${item.bulan} ${item.tahun}'),
        description: Text('Rincian administrasi SPP bulan ${item.bulan}'),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Status Pembayaran:'),
                  ShadBadge(
                    backgroundColor: item.status.background(b),
                    foregroundColor: item.status.color(b),
                    child: Text(item.status.label),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              const ShadSeparator.horizontal(),
              const SizedBox(height: 12),
              _DetailRow(label: 'Nominal Tagihan', value: Formatters.rupiah(item.nominal)),
              _DetailRow(label: 'Jumlah Telah Dibayar', value: Formatters.rupiah(item.dibayar)),
              _DetailRow(
                label: 'Sisa Belum Lunas',
                value: Formatters.rupiah(item.sisa),
                valueColor: item.sisa > 0 ? AppColors.unpaid(b) : AppColors.paid(b),
                isBold: true,
              ),
              if (item.noKuitansi != null) ...[
                const SizedBox(height: 4),
                _DetailRow(
                  label: 'Nomor Kuitansi',
                  value: item.noKuitansi!,
                  isMono: true,
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  const _DetailRow({
    required this.label,
    required this.value,
    this.valueColor,
    this.isBold = false,
    this.isMono = false,
  });

  final String label;
  final String value;
  final Color? valueColor;
  final bool isBold;
  final bool isMono;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 4),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: const TextStyle(fontSize: 13, color: Colors.grey)),
            Text(
              value,
              style: isMono
                  ? AppTheme.mono(fontWeight: FontWeight.w600, color: valueColor)
                  : TextStyle(
                      fontWeight: isBold ? FontWeight.bold : FontWeight.w500,
                      color: valueColor,
                      fontSize: 13,
                    ),
            ),
          ],
        ),
      );
}

class _NonSppList extends StatelessWidget {
  const _NonSppList({required this.items});

  final List<TagihanNonSppItem> items;

  @override
  Widget build(BuildContext context) {
    if (items.isEmpty) {
      return const EmptyView(message: 'Tidak ada tagihan non-SPP.');
    }
    final b = Theme.of(context).brightness;

    return Column(
      children: [
        const SizedBox(height: 12),
        for (final i in items)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: ShadCard(
              title: Text(i.namaPos, style: const TextStyle(fontWeight: FontWeight.bold)),
              trailing: ShadBadge(
                backgroundColor: i.status.background(b),
                foregroundColor: i.status.color(b),
                child: Text(i.status.label),
              ),
              child: Padding(
                padding: const EdgeInsets.only(top: 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    ShadProgress(
                      value: i.progress,
                      color: i.status.color(b),
                    ),
                    const SizedBox(height: 10),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Dibayar: ${Formatters.rupiah(i.dibayar)}',
                          style: const TextStyle(fontSize: 12, color: Colors.grey),
                        ),
                        Text(
                          'Total: ${Formatters.rupiah(i.nominal)}',
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
      ],
    );
  }
}
