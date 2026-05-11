import 'package:dartz/dartz.dart';

import '../../../../core/error_handling/failures.dart';
import '../../../../core/error_handling/request_handler.dart';
import '../../domain/entity/auth_entity.dart';
import '../../domain/payload/login_payload.dart';
import '../../domain/payload/register_payload.dart';
import '../../domain/payload/verify_payload.dart';
import '../../domain/repository/auth_repository.dart';
import '../data_source/auth_local_data_source.dart';
import '../data_source/auth_remote_data_source.dart';
import '../model/auth_model.dart';

class AuthRepositoryImpl implements AuthRepository {
  final AuthDataSource remoteDataSource;
  final AuthLocalDataSource localDataSource;

  AuthRepositoryImpl({
    required this.remoteDataSource,
    required this.localDataSource,
  });

  @override
  Future<Either<Failure, AuthEntity>> login(LoginPayload payload) async {
    return handleRequest(() async {
      final response = await remoteDataSource.login(payload);
      await localDataSource.saveAuthToLocal(response);
      return response;
    });
  }

  @override
  Future<Either<Failure, AuthEntity>> register(RegisterPayload payload) async {
    return handleRequest(() async {
      final response = await remoteDataSource.register(payload);
      await localDataSource.saveAuthToLocal(response);
      return response;
    });
  }

  @override
  Future<Either<Failure, AuthEntity>> verify(VerifyPayload payload) async {
    return handleRequest(() async {
      final response = await remoteDataSource.verify(payload);
      await localDataSource.saveAuthToLocal(response);
      return response;
    });
  }

  @override
  Future<Either<Failure, Unit>> sendOTP(String phoneNumber) async {
    return handleRequest(() async {
      final response = await remoteDataSource.sendOTP(phoneNumber);
      return unit;
    });
  }

  @override
  Future<Either<Failure, Unit>> logout() async {
    return handleRequest(() async {
      await localDataSource.deleteAuthFromLocal();
      await remoteDataSource.logout();
      return unit;
    });
  }

  @override
  Future<Either<Failure, AuthEntity?>> checkToken() async {
    return handleRequest(() async {
      final AuthModel? authModel = await localDataSource.getAuthFromLocal();
      if (authModel != null) {
        return authModel;
      } else {
        return null;
      }
    });
  }

  @override
  Future<Either<Failure, AuthEntity>> signInWithGoogle(String idToken) async {
    return handleRequest(() async {
      final response = await remoteDataSource.signInWithGoogle(idToken);
      await localDataSource.saveAuthToLocal(response);
      return response;
    });
  }
}
