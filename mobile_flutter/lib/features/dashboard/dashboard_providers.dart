import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/constants/api_constants.dart';
import '../../core/network/dio_client.dart';
import '../../core/network/json_cache.dart';
import 'dashboard_models.dart';

final dashboardProvider = FutureProvider.autoDispose<RingkasanKeuangan>((ref) {
  return unwrapWithCache(
    fetch: () => ref.read(dioProvider).get(ApiConstants.studentDashboard),
    cacheKey: ApiConstants.studentDashboard,
    cache: ref.read(jsonCacheProvider),
    parse: (data) => RingkasanKeuangan.fromJson(data as Map<String, dynamic>),
  );
});
