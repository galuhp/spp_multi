import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../../core/notifications/notification_service.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/formatters.dart';
import '../../core/widgets/state_views.dart';
import '../auth/auth_controller.dart';
import 'wali_models.dart';
import 'wali_providers.dart';

class WaliScreen extends ConsumerStatefulWidget {
  const WaliScreen({super.key, this.kelasId = '1'});

  final String kelasId;

  @override
  ConsumerState<WaliScreen> createState() => _WaliScreenState();
}

class _WaliScreenState extends ConsumerState<WaliScreen> {
  String _activeFilter = 'semua';
  String _searchQuery = '';

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(rekapKelasProvider(widget.kelasId));

    return Scaffold(
      body: RefreshIndicator(
        onRefresh: () => ref.refresh(rekapKelasProvider(widget.kelasId).future),
        child: state.when(
          loading: () => ListView(
            padding: EdgeInsets.fromLTRB(
              16,
              MediaQuery.paddingOf(context).top + 16,
              16,
              16,
            ),
            children: const [
              SkeletonBox(height: 180),
              SizedBox(height: 16),
              SkeletonBox(height: 140),
              SizedBox(height: 16),
              SkeletonBox(height: 300),
            ],
          ),
          error: (e, _) => ListView(
            padding: EdgeInsets.fromLTRB(
              16,
              MediaQuery.paddingOf(context).top + 16,
              16,
              16,
            ),
            children: [
              ErrorView(
                message: e.toString(),
                onRetry: () => ref.invalidate(rekapKelasProvider(widget.kelasId)),
              ),
            ],
          ),
          data: (summary) => _WaliContent(
            summary: summary,
            activeFilter: _activeFilter,
            searchQuery: _searchQuery,
            onFilterChanged: (f) => setState(() => _activeFilter = f),
            onSearchChanged: (q) => setState(() => _searchQuery = q),
            onLogout: () => ref.read(authControllerProvider.notifier).logout(),
          ),
        ),
      ),
    );
  }
}

class _WaliContent extends StatelessWidget {
  const _WaliContent({
    required this.summary,
    required this.activeFilter,
    required this.searchQuery,
    required this.onFilterChanged,
    required this.onSearchChanged,
    required this.onLogout,
  });

  final RekapKelasSummary summary;
  final String activeFilter;
  final String searchQuery;
  final ValueChanged<String> onFilterChanged;
  final ValueChanged<String> onSearchChanged;
  final VoidCallback onLogout;

  List<SiswaWaliItem> get _filteredSiswa {
    return summary.siswa.where((s) {
      if (searchQuery.isNotEmpty) {
        final query = searchQuery.toLowerCase();
        final matchName = s.nama.toLowerCase().contains(query);
        final matchNisn = s.nisn.contains(query) || s.nis.contains(query);
        if (!matchName && !matchNisn) return false;
      }
      if (activeFilter == 'menunggak') return !s.isLunas;
      if (activeFilter == 'lunas') return s.isLunas;
      return true;
    }).toList();
  }

  String _initials(String name) {
    final parts = name.trim().split(RegExp(r'\s+'));
    final letters = parts.take(2).map((p) => p.isEmpty ? '' : p[0]).join();
    return letters.isEmpty ? '?' : letters.toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    final theme = ShadTheme.of(context);
    final top = MediaQuery.paddingOf(context).top;
    final filtered = _filteredSiswa;

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: EdgeInsets.zero,
      children: [
        // Header Gradasi Navy
        Container(
          padding: EdgeInsets.fromLTRB(16, top + 12, 16, 24),
          decoration: const BoxDecoration(
            gradient: LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [AppColors.headerStart, AppColors.headerEnd],
            ),
          ),
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
                      _initials(summary.waliKelas),
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
                          'Portal Perwalian',
                          style: TextStyle(
                            color: AppColors.onHeaderMuted,
                            fontSize: 12,
                          ),
                        ),
                        Text(
                          summary.waliKelas,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: theme.textTheme.h4.copyWith(
                            color: AppColors.onHeader,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        Text(
                          summary.namaKelas,
                          style: const TextStyle(
                            color: AppColors.onHeaderMuted,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    tooltip: 'Uji Notifikasi SPP',
                    icon: const Icon(LucideIcons.bellRing, color: AppColors.onHeader),
                    onPressed: () async {
                      await NotificationService().showNotification(
                        id: 99,
                        title: 'Pengingat Rekap SPP',
                        body: 'Ada ${summary.siswaMenunggak} siswa di kelas ${summary.namaKelas} dengan tunggakan.',
                      );
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(
                            content: Text('Notifikasi pengingat SPP terkirim ke sistem.'),
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

              // Kartu Ringkasan Rekap SPP
              ShadCard(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Ringkasan Keuangan Kelas',
                          style: TextStyle(fontWeight: FontWeight.w600),
                        ),
                        Text(
                          '${(summary.persentaseLunas * 100).toStringAsFixed(0)}% Lunas',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            color: AppColors.paid(Theme.of(context).brightness),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    ShadProgress(
                      value: summary.persentaseLunas,
                      color: AppColors.paid(Theme.of(context).brightness),
                    ),
                    const SizedBox(height: 14),
                    Row(
                      children: [
                        Expanded(
                          child: _MetricTile(
                            label: 'Total Siswa',
                            value: '${summary.totalSiswa}',
                            color: Colors.blue.shade700,
                          ),
                        ),
                        Container(width: 1, height: 32, color: Colors.grey.shade300),
                        Expanded(
                          child: _MetricTile(
                            label: 'Lunas',
                            value: '${summary.siswaLunas}',
                            color: AppColors.paid(Theme.of(context).brightness),
                          ),
                        ),
                        Container(width: 1, height: 32, color: Colors.grey.shade300),
                        Expanded(
                          child: _MetricTile(
                            label: 'Menunggak',
                            value: '${summary.siswaMenunggak}',
                            color: AppColors.unpaid(Theme.of(context).brightness),
                          ),
                        ),
                      ],
                    ),
                    const Divider(height: 24),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Total Tunggakan:',
                          style: TextStyle(fontSize: 13, color: Colors.grey),
                        ),
                        Text(
                          'Rp ${Rupiah.format(summary.totalTunggakan)}',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: AppColors.unpaid(Theme.of(context).brightness),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),

        // Search & Filter
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              ShadInput(
                placeholder: const Text('Cari nama siswa, NISN, atau NIS...'),
                leading: const Padding(
                  padding: EdgeInsets.only(right: 8),
                  child: Icon(LucideIcons.search, size: 18),
                ),
                onChanged: onSearchChanged,
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  _FilterChip(
                    label: 'Semua (${summary.totalSiswa})',
                    isSelected: activeFilter == 'semua',
                    onTap: () => onFilterChanged('semua'),
                  ),
                  const SizedBox(width: 8),
                  _FilterChip(
                    label: 'Menunggak (${summary.siswaMenunggak})',
                    isSelected: activeFilter == 'menunggak',
                    selectedColor: AppColors.unpaid(Theme.of(context).brightness),
                    onTap: () => onFilterChanged('menunggak'),
                  ),
                  const SizedBox(width: 8),
                  _FilterChip(
                    label: 'Lunas (${summary.siswaLunas})',
                    isSelected: activeFilter == 'lunas',
                    selectedColor: AppColors.paid(Theme.of(context).brightness),
                    onTap: () => onFilterChanged('lunas'),
                  ),
                ],
              ),
            ],
          ),
        ),

        // List Siswa
        if (filtered.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 40, horizontal: 16),
            child: EmptyView(message: 'Tidak ada data siswa sesuai kriteria.'),
          )
        else
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Column(
              children: filtered
                  .map(
                    (s) => _SiswaWaliCard(
                      siswa: s,
                      namaKelas: summary.namaKelas,
                      namaWali: summary.waliKelas,
                    ),
                  )
                  .toList(),
            ),
          ),
        const SizedBox(height: 24),
      ],
    );
  }
}

class _MetricTile extends StatelessWidget {
  const _MetricTile({
    required this.label,
    required this.value,
    required this.color,
  });

  final String label;
  final String value;
  final Color color;

  @override
  Widget build(BuildContext context) => Column(
        children: [
          Text(
            value,
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: color,
            ),
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: const TextStyle(fontSize: 12, color: Colors.grey),
          ),
        ],
      );
}

class _FilterChip extends StatelessWidget {
  const _FilterChip({
    required this.label,
    required this.isSelected,
    required this.onTap,
    this.selectedColor,
  });

  final String label;
  final bool isSelected;
  final VoidCallback onTap;
  final Color? selectedColor;

  @override
  Widget build(BuildContext context) {
    final activeCol = selectedColor ?? Theme.of(context).colorScheme.primary;
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? activeCol.withValues(alpha: 0.15) : Colors.transparent,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected ? activeCol : Colors.grey.shade400,
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Text(
          label,
          style: TextStyle(
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
            color: isSelected ? activeCol : Colors.grey.shade700,
          ),
        ),
      ),
    );
  }
}

class _SiswaWaliCard extends StatelessWidget {
  const _SiswaWaliCard({
    required this.siswa,
    required this.namaKelas,
    required this.namaWali,
  });

  final SiswaWaliItem siswa;
  final String namaKelas;
  final String namaWali;

  @override
  Widget build(BuildContext context) {
    final brightness = Theme.of(context).brightness;

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: ShadCard(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                CircleAvatar(
                  radius: 20,
                  backgroundColor: Colors.blue.shade100,
                  child: Text(
                    siswa.nama.isNotEmpty ? siswa.nama[0].toUpperCase() : '?',
                    style: TextStyle(
                      color: Colors.blue.shade900,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        siswa.nama,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 15,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'NISN: ${siswa.nisn} • NIS: ${siswa.nis}',
                        style: TextStyle(fontSize: 12, color: Colors.grey.shade600),
                      ),
                    ],
                  ),
                ),
                if (siswa.isLunas)
                  const ShadBadge(child: Text('Lunas'))
                else
                  const ShadBadge.destructive(child: Text('Menunggak')),
              ],
            ),
            const Divider(height: 20),
            if (siswa.isLunas)
              Row(
                children: [
                  Icon(
                    LucideIcons.checkCircle2,
                    size: 16,
                    color: AppColors.paid(brightness),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'Seluruh tagihan SPP telah terselesaikan',
                    style: TextStyle(
                      fontSize: 13,
                      color: AppColors.paid(brightness),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              )
            else ...[
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Bulan: ${siswa.bulanMenunggak.join(", ")}',
                    style: TextStyle(fontSize: 12, color: Colors.grey.shade700),
                  ),
                  Text(
                    'Rp ${Rupiah.format(siswa.totalTunggakan)}',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.bold,
                      color: AppColors.unpaid(brightness),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: ShadButton.outline(
                  leading: const Icon(LucideIcons.messageSquare, size: 16),
                  onPressed: () async {
                    final msg = siswa.generateWhatsAppMessage(
                      namaKelas: namaKelas,
                      namaWali: namaWali,
                    );
                    final ok = await launchWhatsAppReminder(
                      phoneNumber: siswa.noHpWali,
                      message: msg,
                    );
                    if (!ok && context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(
                            'Tidak dapat membuka WhatsApp untuk nomor ${siswa.noHpWali}. Pastikan aplikasi WhatsApp terpasang.',
                          ),
                          backgroundColor: Colors.red.shade700,
                        ),
                      );
                    }
                  },
                  child: const Text('Ingatkan Orang Tua via WhatsApp'),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
