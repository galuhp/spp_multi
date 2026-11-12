import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/constants/api_constants.dart';
import '../../core/network/dio_client.dart';
import 'dashboard_models.dart';

final dashboardProvider = FutureProvider.autoDispose<RingkasanKeuangan>((ref) {
  return unwrap(
    ref.read(dioProvider).get(ApiConstants.studentDashboard),
    (data) => RingkasanKeuangan.fromJson(data as Map<String, dynamic>),
  );
});
