import 'package:dartz/dartz.dart';

import '../../../../core/error_handling/failures.dart';
import '../entity/auth_entity.dart';
import '../repository/auth_repository.dart';

class CheckTokenUseCase {
  AuthRepository repository;

  CheckTokenUseCase({required this.repository});

  Future<Either<Failure, AuthEntity?>> call() async {
    return await repository.checkToken();
  }
}
