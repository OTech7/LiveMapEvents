import 'package:dartz/dartz.dart';

import '../../../../core/error_handling/failures.dart';
import '../entity/auth_entity.dart';
import '../payload/login_payload.dart';
import '../repository/auth_repository.dart';

class LoginUseCase {
  AuthRepository repository;

  LoginUseCase({required this.repository});

  Future<Either<Failure, AuthEntity>> call(LoginPayload payload) async {
    return await repository.login(payload);
  }
}
