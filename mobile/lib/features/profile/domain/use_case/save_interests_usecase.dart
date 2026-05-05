import 'package:dartz/dartz.dart';
import '../../../../core/error_handling/failures.dart';
import '../repository/profile_repository.dart';

class SaveInterestsUseCase {
  final ProfileRepository repository;

  SaveInterestsUseCase(this.repository);

  Future<Either<Failure, Unit>> call(List<String> interests) {
    return repository.saveInterests(interests);
  }
}
