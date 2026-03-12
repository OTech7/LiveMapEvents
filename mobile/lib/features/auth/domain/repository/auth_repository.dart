import 'package:dartz/dartz.dart';
import '../../../../core/error_handling/failures.dart';
import '../entity/auth_entity.dart';
import '../payload/login_payload.dart';
import '../payload/register_payload.dart';

abstract class AuthRepository {
  Future<Either<Failure, AuthEntity>> login(LoginPayload payload);

  Future<Either<Failure, AuthEntity>> register(RegisterPayload payload);

  Future<Either<Failure, Unit>> logout();

  Future<Either<Failure, AuthEntity?>> checkToken();
}
