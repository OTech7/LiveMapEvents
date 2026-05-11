import 'package:dartz/dartz.dart';

import '../../../../core/error_handling/failures.dart';
import '../repository/auth_repository.dart';

class LogoutUseCase {
  AuthRepository repository;

  LogoutUseCase({required this.repository});

  Future<Either<Failure, Unit>> call() async {
    return await repository.logout();
  }
}
