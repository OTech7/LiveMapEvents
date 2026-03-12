import 'package:dartz/dartz.dart';

import '../../../../core/error_handling/failures.dart';
import '../entity/auth_entity.dart';
import '../payload/register_payload.dart';
import '../repository/auth_repository.dart';

class RegisterUseCase {
  AuthRepository repository;

  RegisterUseCase({required this.repository});

  Future<Either<Failure, AuthEntity>> call(RegisterPayload payload) async {
    return await repository.register(payload);
  }
}
