import 'package:dartz/dartz.dart';
import 'package:dio/dio.dart';
import '../../../../core/network/api_endpoints.dart';
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
  Future<Unit> sendOTP(String phoneNumber);
  Future<AuthModel> signInWithGoogle(String idToken);
}

class AuthDataSourceImpl implements AuthDataSource {
  final AppInterceptor interceptor;

  AuthDataSourceImpl({required this.interceptor});

  @override
  Future<AuthModel> login(LoginPayload payload) async {
    final response = await interceptor.get(EndPoints.login);
    return AuthModel.fromJson(response.data["data"]);
  }

  @override
  Future<Unit> sendOTP(String phoneNumber) async {
    final response = await interceptor.post(
      EndPoints.sendOTP,
      body: {"phone": phoneNumber},
    );
    return unit;
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
    final response = await interceptor.post(
      EndPoints.verify,
      body: payload.toJson(),
    );
    return AuthModel.fromJson(response.data["data"]);
  }

  @override
  Future<AuthModel> signInWithGoogle(String idToken) async {
    final response = await interceptor.dio.post(
      EndPoints.googleAuth,
      data: FormData.fromMap({'id_token': idToken}),
      options: Options(headers: {"Accept": "application/json"}),
    );

    final data = response.data['data'];
    return AuthModel(
      token: data['token'] ?? '',
      refreshToken: '',
    );
  }
}
