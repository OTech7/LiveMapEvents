import 'package:dartz/dartz.dart';
import '../../../../core/network/api_endpoints.dart';
import '../../../../core/network/interceptor.dart';
import '../../domain/payload/login_payload.dart';
import '../../domain/payload/register_payload.dart';
import '../../domain/payload/verify_payload.dart';
import '../model/auth_model.dart';

abstract class AuthDataSource {
  Future<AuthModel> login(LoginPayload payload);

  Future<AuthModel> register(RegisterPayload payload);

  Future<AuthModel> verify(VerifyPayload payload);

  Future<Unit> logout();
}

class AuthDataSourceImpl implements AuthDataSource {
  final AppInterceptor interceptor;

  AuthDataSourceImpl({required this.interceptor});

  @override
  Future<AuthModel> login(LoginPayload payload) async {
    final response =
        await interceptor.post(EndPoints.login, body: payload.toJson());
    return AuthModel.fromJson(response.data);
  }

  @override
  Future<AuthModel> register(RegisterPayload payload) async {
    final response =
        await interceptor.post(EndPoints.register, body: payload.toJson());
    return AuthModel.fromJson(response.data);
  }

  @override
  Future<Unit> logout() async {
    await interceptor.get(EndPoints.logout);
    return unit;
  }

  @override
  Future<AuthModel> verify(VerifyPayload payload) async {
    final response = await interceptor.post(EndPoints.verify, body: payload.toJson());
    return AuthModel.fromJson(response.data);
  }
}
