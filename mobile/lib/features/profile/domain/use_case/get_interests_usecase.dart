import 'package:dartz/dartz.dart';
import '../../../../core/error_handling/failures.dart';
import '../entity/interest_entity.dart';
import '../repository/profile_repository.dart';

class GetInterestsUseCase {
  final ProfileRepository repository;

  GetInterestsUseCase(this.repository);

  Future<Either<Failure, List<InterestEntity>>> call() async {
    return await repository.getInterests();
  }
}
