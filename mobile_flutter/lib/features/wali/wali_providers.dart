import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../core/constants/api_constants.dart';
import '../../core/network/dio_client.dart';
import 'wali_models.dart';

/// Provider data rekap kelas perwalian berdasarkan ID kelas.
final rekapKelasProvider =
    FutureProvider.family<RekapKelasSummary, String>((ref, kelasId) async {
  final dio = ref.watch(dioProvider);
  return unwrap(
    dio.get(ApiConstants.waliRekap(kelasId)),
    (data) => RekapKelasSummary.fromJson(data as Map<String, dynamic>),
  );
});

/// Provider daftar siswa yang menunggak secara spesifik.
final tunggakanWaliProvider =
    FutureProvider<List<SiswaWaliItem>>((ref) async {
  final dio = ref.watch(dioProvider);
  return unwrap(
    dio.get(ApiConstants.waliTunggakan),
    (data) {
      final list = (data as Map<String, dynamic>)['siswa'] as List<dynamic>?;
      return list
              ?.map((e) => SiswaWaliItem.fromJson(e as Map<String, dynamic>))
              .toList() ??
          [];
    },
  );
});

/// Utilitas untuk membuka WhatsApp dengan pesan pengingat tagihan resmi.
Future<bool> launchWhatsAppReminder({
  required String phoneNumber,
  required String message,
}) async {
  var cleanNumber = phoneNumber.replaceAll(RegExp(r'[^0-9]'), '');
  if (cleanNumber.startsWith('0')) {
    cleanNumber = '62${cleanNumber.substring(1)}';
  }
  if (cleanNumber.isEmpty) return false;

  final uri = Uri.parse(
    'https://wa.me/$cleanNumber?text=${Uri.encodeComponent(message)}',
  );

  if (await canLaunchUrl(uri)) {
    return await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
  return false;
}
