import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../notifications/notification_service.dart';

/// Pesan push yang sudah dinormalisasi untuk aplikasi.
class PushMessage {
  const PushMessage({
    required this.title,
    required this.body,
    this.route,
    this.data = const {},
  });

  final String title;
  final String body;
  final String? route;
  final Map<String, String> data;

  /// Membaca payload notifikasi (FCM data message maupun web push).
  factory PushMessage.fromData(Map<String, dynamic> payload) => PushMessage(
        title: payload['title']?.toString() ?? 'E-SPP Sekolah',
        body: payload['body']?.toString() ?? '',
        route: payload['route']?.toString(),
        data: payload.map((key, value) => MapEntry(key, value.toString())),
      );
}

/// Transport push (FCM/APNs/Web Push).
///
/// Implementasi konkret dipasang saat kredensial Firebase tersedia; sebelum
/// itu aplikasi memakai [NoopPushTransport] agar build tetap berjalan.
abstract interface class PushTransport {
  /// Mengembalikan device token, atau `null` bila push belum dikonfigurasi.
  Future<String?> initialize();

  /// Pesan yang masuk saat aplikasi aktif.
  Stream<PushMessage> get messages;

  /// Berlangganan topik, mis. `wali-kelas` atau `kelas-<id>`.
  Future<void> subscribeToTopic(String topic);
}

class NoopPushTransport implements PushTransport {
  @override
  Future<String?> initialize() async => null;

  @override
  Stream<PushMessage> get messages => const Stream.empty();

  @override
  Future<void> subscribeToTopic(String topic) async {}
}

/// Menerima pesan push lalu menampilkannya sebagai notifikasi lokal.
class PushService {
  PushService(this._transport, this._notifications);

  final PushTransport _transport;
  final NotificationService _notifications;

  StreamSubscription<PushMessage>? _subscription;
  String? _token;

  /// Device token terakhir (null bila push belum dikonfigurasi).
  String? get token => _token;

  Future<void> initialize() async {
    _token = await _transport.initialize();
    _subscription = _transport.messages.listen(_handleMessage);
    debugPrint(
      'PushService siap (token: ${_token ?? 'belum dikonfigurasi'})',
    );
  }

  Future<void> subscribeToTopic(String topic) =>
      _transport.subscribeToTopic(topic);

  void _handleMessage(PushMessage message) {
    _notifications.showNotification(
      id: message.hashCode & 0x7fffffff,
      title: message.title,
      body: message.body,
      payload: message.route,
    );
  }

  Future<void> dispose() async {
    await _subscription?.cancel();
    _subscription = null;
  }
}

final pushTransportProvider = Provider<PushTransport>((ref) {
  // Ganti dengan implementasi FCM setelah google-services.json /
  // GoogleService-Info.plist tersedia (lihat catatan integrasi).
  return NoopPushTransport();
});

final pushServiceProvider = Provider<PushService>((ref) {
  return PushService(ref.read(pushTransportProvider), NotificationService());
});
