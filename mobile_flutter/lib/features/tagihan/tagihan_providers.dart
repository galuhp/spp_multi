import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/constants/api_constants.dart';
import '../../core/network/dio_client.dart';
import 'tagihan_models.dart';

final kartuSppProvider = FutureProvider.autoDispose<KartuSpp>((ref) {
  return unwrap(
    ref.read(dioProvider).get(ApiConstants.studentCard),
    (data) => KartuSpp.fromJson(data as Map<String, dynamic>),
  );
});
