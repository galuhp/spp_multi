import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/state_views.dart';
import 'tagihan_models.dart';
import 'tagihan_providers.dart';

class TagihanScreen extends ConsumerWidget {
  const TagihanScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(kartuSppProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('Tagihan')),
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(kartuSppProvider.future),
        child: state.when(
          loading: () => ListView(
            padding: const EdgeInsets.all(16),
            children: const [SkeletonBox(height: 300)],
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
          data: (kartu) => ShadTabs<String>(
            value: 'spp',
            tabBarConstraints: const BoxConstraints(maxWidth: 400),
            contentConstraints: const BoxConstraints(maxWidth: 400),
            tabs: [
              ShadTab(
                value: 'spp',
                content: _SppGrid(items: kartu.spp),
                child: const Text('SPP Bulanan'),
              ),
              ShadTab(
                value: 'non',
                content: _NonSppList(items: kartu.nonSpp),
                child: const Text('Non-SPP'),
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
    final cellHeight = MediaQuery.textScalerOf(context).scale(88);
    return GridView(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.symmetric(vertical: 12),
      gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        mainAxisSpacing: 8,
        crossAxisSpacing: 8,
        mainAxisExtent: cellHeight < 88 ? 88 : cellHeight,
      ),
      children: [for (final i in items) _BulanCell(item: i)],
    );
  }
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
      label: '${item.bulan} ${item.tahun}, ${s.label}',
      child: Material(
        color: s.background(b),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(14),
          side: BorderSide(color: fg.withValues(alpha: 0.3)),
        ),
        child: InkWell(
          customBorder: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(14)),
          onTap: () => _showDetail(context),
          child: Padding(
            padding: const EdgeInsets.all(8),
            child: ExcludeSemantics(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    item.bulan.length > 3
                        ? item.bulan.substring(0, 3)
                        : item.bulan,
                    style: TextStyle(fontWeight: FontWeight.w700, color: fg),
                  ),
                  const SizedBox(height: 4),
                  Icon(_iconFor(s), size: 16, color: fg),
                  const SizedBox(height: 4),
                  Text(
                    s.label,
                    textAlign: TextAlign.center,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(fontSize: 12, color: fg),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  /// Ikon pendamping agar status tidak hanya dibedakan lewat warna.
  static IconData _iconFor(StatusTagihan s) => switch (s) {
        StatusTagihan.lunas => LucideIcons.circleCheck,
        StatusTagihan.sebagian => LucideIcons.circleDot,
        StatusTagihan.belum => LucideIcons.circleAlert,
        StatusTagihan.akanDatang => LucideIcons.clock,
        StatusTagihan.ditanggung => LucideIcons.shieldCheck,
      };

  void _showDetail(BuildContext context) => showShadSheet(
        context: context,
        side: ShadSheetSide.bottom,
        builder: (_) => ShadSheet(
          title: Text('${item.bulan} ${item.tahun}'),
          description: Text(item.status.label),
          child: Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('Nominal: ${Formatters.rupiah(item.nominal)}'),
                Text('Dibayar: ${Formatters.rupiah(item.dibayar)}'),
                Text('Sisa: ${Formatters.rupiah(item.sisa)}'),
                if (item.noKuitansi != null)
                  Text('Kuitansi: ${item.noKuitansi}',
                      style: AppTheme.mono()),
              ],
            ),
          ),
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
    return Column(
      children: [
        for (final i in items)
          Padding(
            padding: const EdgeInsets.only(top: 12),
            child: ShadCard(
              title: Text(i.namaPos),
              trailing: ShadBadge(
                backgroundColor:
                    i.status.background(Theme.of(context).brightness),
                foregroundColor: i.status.color(Theme.of(context).brightness),
                child: Text(i.status.label),
              ),
              child: Padding(
                padding: const EdgeInsets.only(top: 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    ShadProgress(
                      value: i.progress,
                      color: i.status.color(Theme.of(context).brightness),
                    ),
                    const SizedBox(height: 8),
                    Text(
                        '${Formatters.rupiah(i.dibayar)} dari ${Formatters.rupiah(i.nominal)}'),
                  ],
                ),
              ),
            ),
          ),
      ],
    );
  }
}
