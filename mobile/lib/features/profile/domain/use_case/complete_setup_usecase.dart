import 'package:dartz/dartz.dart';
import '../../../../core/error_handling/failures.dart';
import '../payload/complete_setup_payload.dart';
import '../repository/profile_repository.dart';

class CompleteSetupUseCase {
  final ProfileRepository repository;

  CompleteSetupUseCase(this.repository);

  Future<Either<Failure, Unit>> call(CompleteSetupPayload payload) async {
    return await repository.completeSetup(payload);
  }
}
