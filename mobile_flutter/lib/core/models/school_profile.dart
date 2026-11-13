import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../constants/api_constants.dart';
import '../network/dio_client.dart';
import '../network/json_cache.dart';

/// Profil sekolah untuk kop kuitansi & laporan, mengikuti data
/// `ProfilSekolah` pada aplikasi Laravel.
class SchoolProfile {
  const SchoolProfile({
    this.nama = 'SMK Negeri Contoh',
    this.alamat = 'Jl. Pendidikan No. 1',
    this.telepon = '(022) 1234567',
    this.website = 'www.smknegericontoh.sch.id',
    this.npsn = '20219876',
    this.kota = 'Bandung',
  });

  final String nama;
  final String alamat;
  final String telepon;
  final String website;
  final String npsn;
  final String kota;

  String get inisial {
    final trimmed = nama.trim();
    return trimmed.isEmpty ? '?' : trimmed[0].toUpperCase();
  }

  factory SchoolProfile.fromJson(Map<String, dynamic> json) {
    const fallback = SchoolProfile();
    String read(String key, String fallbackValue) {
      final value = json[key]?.toString().trim();
      return (value == null || value.isEmpty) ? fallbackValue : value;
    }

    return SchoolProfile(
      nama: read('nama_sekolah', fallback.nama),
      alamat: read('alamat', fallback.alamat),
      telepon: read('no_telp', fallback.telepon),
      website: read('website', fallback.website),
      npsn: read('npsn', fallback.npsn),
      kota: read('kota', fallback.kota),
    );
  }

  /// Dipakai bila endpoint profil sekolah belum tersedia.
  static const fallback = SchoolProfile();
}

final schoolProfileProvider = FutureProvider<SchoolProfile>((ref) async {
  try {
    return await unwrapWithCache(
      fetch: () => ref.read(dioProvider).get(ApiConstants.schoolProfile),
      cacheKey: ApiConstants.schoolProfile,
      cache: ref.read(jsonCacheProvider),
      parse: (data) => SchoolProfile.fromJson(data as Map<String, dynamic>),
    );
  } catch (_) {
    return SchoolProfile.fallback;
  }
});
