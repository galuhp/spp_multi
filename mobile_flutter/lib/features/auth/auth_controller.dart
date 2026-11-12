import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/constants/api_constants.dart';
import '../../core/network/dio_client.dart';
import '../../core/storage/token_storage.dart';
import 'auth_models.dart';

enum AuthStatus { unknown, signedOut, signedIn }

class AuthController extends StateNotifier<AuthStatus> {
  AuthController(this._ref) : super(AuthStatus.unknown) {
    _restore();
    _ref.listen<int>(unauthorizedEventProvider, (_, __) {
      state = AuthStatus.signedOut;
    });
  }

  final Ref _ref;

  Future<void> _restore() async {
    final token = await _ref.read(tokenStorageProvider).read();
    state = token == null ? AuthStatus.signedOut : AuthStatus.signedIn;
  }

  Future<void> login(String username, String password,
      {bool remember = true}) async {
    final session = await unwrap(
      _ref.read(dioProvider).post(
        ApiConstants.login,
        data: {'username': username, 'password': password},
      ),
      (data) => AuthSession.fromJson(data as Map<String, dynamic>),
    );
    await _ref.read(tokenStorageProvider).write(
          session.token,
          role: session.role,
          name: session.name,
          persist: remember,
        );
    state = AuthStatus.signedIn;
  }

  Future<void> logout() async {
    await _ref.read(tokenStorageProvider).clear();
    state = AuthStatus.signedOut;
  }
}

final authControllerProvider =
    StateNotifierProvider<AuthController, AuthStatus>(AuthController.new);

final userRoleProvider = FutureProvider<String?>((ref) async {
  final status = ref.watch(authControllerProvider);
  if (status != AuthStatus.signedIn) return null;
  return ref.read(tokenStorageProvider).readRole();
});

final userNameProvider = FutureProvider<String?>((ref) async {
  final status = ref.watch(authControllerProvider);
  if (status != AuthStatus.signedIn) return null;
  return ref.read(tokenStorageProvider).readName();
});
