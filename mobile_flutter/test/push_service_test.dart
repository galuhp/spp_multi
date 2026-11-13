import 'dart:async';

import 'package:espp_mobile/core/notifications/notification_service.dart';
import 'package:espp_mobile/core/push/push_service.dart';
import 'package:flutter_test/flutter_test.dart';

class _FakeTransport implements PushTransport {
  _FakeTransport({this.initialToken});

  final String? initialToken;
  final _controller = StreamController<PushMessage>.broadcast();
  final List<String> topics = [];
  bool initialized = false;

  @override
  Future<String?> initialize() async {
    initialized = true;
    return initialToken;
  }

  @override
  Stream<PushMessage> get messages => _controller.stream;

  @override
  Future<void> subscribeToTopic(String topic) async => topics.add(topic);

  void emit(PushMessage message) => _controller.add(message);
}

class _RecordingNotifications extends NotificationService {
  final List<({String title, String body, String? payload})> shown = [];

  @override
  Future<void> showNotification({
    required int id,
    required String title,
    required String body,
    String? payload,
  }) async {
    shown.add((title: title, body: body, payload: payload));
  }
}

void main() {
  group('PushMessage', () {
    test('membaca payload notifikasi dengan nilai non-string', () {
      final message = PushMessage.fromData({
        'title': 'Tagihan SPP',
        'body': 'Jatuh tempo 10 November',
        'route': '/tagihan',
        'tagihan_id': 42,
      });

      expect(message.title, 'Tagihan SPP');
      expect(message.body, 'Jatuh tempo 10 November');
      expect(message.route, '/tagihan');
      expect(message.data['tagihan_id'], '42');
    });

    test('memakai judul default bila payload kosong', () {
      final message = PushMessage.fromData({});

      expect(message.title, 'E-SPP Sekolah');
      expect(message.body, isEmpty);
      expect(message.route, isNull);
    });
  });

  group('PushService', () {
    test('menyimpan token dari transport saat inisialisasi', () async {
      final transport = _FakeTransport(initialToken: 'token-abc');
      final service = PushService(transport, _RecordingNotifications());

      await service.initialize();

      expect(transport.initialized, isTrue);
      expect(service.token, 'token-abc');
    });

    test('menampilkan pesan masuk sebagai notifikasi lokal', () async {
      final transport = _FakeTransport(initialToken: 'token-abc');
      final notifications = _RecordingNotifications();
      final service = PushService(transport, notifications);
      await service.initialize();

      transport.emit(const PushMessage(
        title: 'Pengingat SPP',
        body: 'Tagihan November sebesar Rp 250.000',
        route: '/tagihan',
      ));
      await Future<void>.delayed(Duration.zero);

      expect(notifications.shown, hasLength(1));
      expect(notifications.shown.single.title, 'Pengingat SPP');
      expect(notifications.shown.single.payload, '/tagihan');
    });

    test('meneruskan langganan topik ke transport', () async {
      final transport = _FakeTransport();
      final service = PushService(transport, _RecordingNotifications());
      await service.initialize();

      await service.subscribeToTopic('kelas-12-rpl-1');

      expect(transport.topics, ['kelas-12-rpl-1']);
    });

    test('berhenti menerima pesan setelah dispose', () async {
      final transport = _FakeTransport();
      final notifications = _RecordingNotifications();
      final service = PushService(transport, notifications);
      await service.initialize();
      await service.dispose();

      transport.emit(const PushMessage(title: 'x', body: 'y'));
      await Future<void>.delayed(Duration.zero);

      expect(notifications.shown, isEmpty);
    });

    test('NoopPushTransport tidak menghasilkan token', () async {
      final service =
          PushService(NoopPushTransport(), _RecordingNotifications());

      await service.initialize();

      expect(service.token, isNull);
    });
  });
}
