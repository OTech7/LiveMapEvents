import 'package:dartz/dartz.dart';

import '../../../../core/error_handling/failures.dart';
import '../entity/auth_entity.dart';
import '../payload/verify_payload.dart';
import '../repository/auth_repository.dart';

class VerifyUseCase {
  AuthRepository repository;

  VerifyUseCase({required this.repository});

  Future<Either<Failure, AuthEntity>> call(VerifyPayload payload) async {
    return await repository.verify(payload);
  }
}
