import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/constants/api_constants.dart';
import '../../core/network/dio_client.dart';
import '../../core/network/json_cache.dart';
import 'tagihan_models.dart';

final kartuSppProvider = FutureProvider.autoDispose<KartuSpp>((ref) {
  return unwrapWithCache(
    fetch: () => ref.read(dioProvider).get(ApiConstants.studentCard),
    cacheKey: ApiConstants.studentCard,
    cache: ref.read(jsonCacheProvider),
    parse: (data) => KartuSpp.fromJson(data as Map<String, dynamic>),
  );
});
