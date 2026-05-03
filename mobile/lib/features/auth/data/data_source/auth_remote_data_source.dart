import 'package:dartz/dartz.dart';
import '../../../../core/network/interceptor.dart';
import '../../domain/payload/login_payload.dart';
import '../../domain/payload/register_payload.dart';
import '../../domain/payload/verify_payload.dart';
import '../model/auth_model.dart';

abstract class AuthDataSource {
  Future<AuthModel> login(LoginPayload payload);

  Future<AuthModel> register(RegisterPayload payload);

  Future<Unit> logout();
  Future<AuthModel> verify(VerifyPayload payload);
}

class AuthDataSourceImpl implements AuthDataSource {
  final AppInterceptor interceptor;

  AuthDataSourceImpl({required this.interceptor});

  @override
  Future<AuthModel> login(LoginPayload payload) async {
    // Simulation of network delay
    await Future.delayed(const Duration(seconds: 1));
    return AuthModel(
      token: "fake_access_token_${payload.email}",
      refreshToken: "fake_refresh_token",
    );
  }

  @override
  Future<AuthModel> register(RegisterPayload payload) async {
    await Future.delayed(const Duration(seconds: 1));
    return AuthModel(
      token: "fake_register_token_${payload.email}",
      refreshToken: "fake_refresh_token",
    );
  }

  @override
  Future<Unit> logout() async {
    await Future.delayed(const Duration(milliseconds: 500));
    return unit;
  }

  @override
  Future<AuthModel> verify(VerifyPayload payload) async {
    await Future.delayed(const Duration(seconds: 1));
    return AuthModel(
      token: "fake_verified_token",
      refreshToken: "fake_refresh_token",
    );
  }

}
