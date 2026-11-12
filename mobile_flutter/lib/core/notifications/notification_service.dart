import 'package:flutter/foundation.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

/// Service penanganan notifikasi lokal pengingat pembayaran SPP.
class NotificationService {
  NotificationService([FlutterLocalNotificationsPlugin? plugin])
      : _plugin = plugin ?? FlutterLocalNotificationsPlugin();

  final FlutterLocalNotificationsPlugin _plugin;
  bool _initialized = false;

  static const String channelId = 'espp_spp_reminders';
  static const String channelName = 'Pengingat Pembayaran SPP';
  static const String channelDesc = 'Notifikasi jatuh tempo dan konfirmasi pembayaran SPP';

  /// Inisialisasi kanal dan izin notifikasi perangkat.
  Future<bool> initialize() async {
    if (_initialized) return true;

    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const darwinSettings = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );

    const initSettings = InitializationSettings(
      android: androidSettings,
      iOS: darwinSettings,
    );

    try {
      final success = await _plugin.initialize(
        settings: initSettings,
        onDidReceiveNotificationResponse: (NotificationResponse res) {
          debugPrint('Notifikasi diketuk: ${res.payload}');
        },
      );

      // Minta izin pada Android 13+ (API 33+)
      final androidPlatform = _plugin.resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin>();
      if (androidPlatform != null) {
        await androidPlatform.requestNotificationsPermission();
      }

      _initialized = success ?? false;
      return _initialized;
    } catch (e) {
      debugPrint('NotificationService init error (graceful): $e');
      return false;
    }
  }

  /// Menampilkan notifikasi lokal instan (misal: saat transaksi berhasil atau jatuh tempo).
  Future<void> showNotification({
    required int id,
    required String title,
    required String body,
    String? payload,
  }) async {
    const androidDetails = AndroidNotificationDetails(
      channelId,
      channelName,
      channelDescription: channelDesc,
      importance: Importance.high,
      priority: Priority.high,
      icon: '@mipmap/ic_launcher',
    );

    const darwinDetails = DarwinNotificationDetails(
      presentAlert: true,
      presentBadge: true,
      presentSound: true,
    );

    const notificationDetails = NotificationDetails(
      android: androidDetails,
      iOS: darwinDetails,
    );

    try {
      await _plugin.show(
        id: id,
        title: title,
        body: body,
        notificationDetails: notificationDetails,
        payload: payload,
      );
    } catch (e) {
      debugPrint('Gagal menampilkan notifikasi (graceful): $e');
    }
  }

  /// Menjadwalkan pengingat SPP bulanan menjelang tanggal 10.
  Future<void> scheduleMonthlyReminder({
    required String bulan,
    required num nominal,
    int dueDay = 10,
  }) async {
    await showNotification(
      id: 1001,
      title: 'Pengingat Tagihan SPP $bulan',
      body: 'Jatuh tempo SPP tanggal $dueDay. Tagihan: Rp $nominal. Ketuk untuk rincian.',
      payload: '/tagihan',
    );
  }
}
