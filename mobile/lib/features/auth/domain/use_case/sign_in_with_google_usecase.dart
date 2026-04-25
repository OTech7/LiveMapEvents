import 'package:dartz/dartz.dart';
import '../../../../core/error_handling/failures.dart';
import '../entity/auth_entity.dart';
import '../repository/auth_repository.dart';

class SignInWithGoogleUseCase {
  final AuthRepository repository;

  SignInWithGoogleUseCase(this.repository);

  Future<Either<Failure, AuthEntity>> call(String idToken) async {
    return await repository.signInWithGoogle(idToken);
  }
}
