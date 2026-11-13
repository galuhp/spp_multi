import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Cache JSON kecil lintas platform (web, Android, iOS) tanpa dependency baru.
///
/// Dipakai agar layar utama tetap bisa dibuka saat jaringan bermasalah.
class JsonCache {
  JsonCache([
    FlutterSecureStorage? storage,
    DateTime Function()? clock,
  ])  : _storage = storage ?? const FlutterSecureStorage(),
        _clock = clock ?? DateTime.now;

  /// Umur default data cache.
  static const defaultMaxAge = Duration(hours: 12);

  static const _prefix = 'json_cache:';

  final FlutterSecureStorage _storage;
  final DateTime Function() _clock;

  Future<void> write(String key, Object? data) async {
    try {
      await _storage.write(
        key: '$_prefix$key',
        value: jsonEncode({
          'saved_at': _clock().toIso8601String(),
          'data': data,
        }),
      );
    } catch (e) {
      debugPrint('JsonCache write gagal: $e');
    }
  }

  /// Mengembalikan data cache, atau `null` bila tidak ada/kedaluwarsa.
  Future<Object?> read(String key, {Duration maxAge = defaultMaxAge}) async {
    try {
      final raw = await _storage.read(key: '$_prefix$key');
      if (raw == null) return null;

      final entry = jsonDecode(raw) as Map<String, dynamic>;
      final savedAt = DateTime.tryParse(entry['saved_at'] as String? ?? '');
      if (savedAt == null) return null;
      if (_clock().difference(savedAt) > maxAge) return null;
      return entry['data'];
    } catch (e) {
      debugPrint('JsonCache read gagal: $e');
      return null;
    }
  }

  Future<void> remove(String key) async {
    try {
      await _storage.delete(key: '$_prefix$key');
    } catch (e) {
      debugPrint('JsonCache hapus gagal: $e');
    }
  }
}

final jsonCacheProvider = Provider<JsonCache>((ref) => JsonCache());
