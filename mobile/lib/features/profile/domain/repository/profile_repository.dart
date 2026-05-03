import 'package:dartz/dartz.dart';
import '../../../../core/error_handling/failures.dart';
import '../entity/interest_entity.dart';
import '../payload/complete_setup_payload.dart';

abstract class ProfileRepository {
  Future<Either<Failure, List<InterestEntity>>> getInterests();

  Future<Either<Failure, Unit>> completeSetup(CompleteSetupPayload payload);
}
