import 'package:dartz/dartz.dart';
import 'package:mobile/features/profile/data/data_source/profile_remote_data_source.dart';

import '../../../../core/error_handling/failures.dart';
import '../../../../core/error_handling/request_handler.dart';
import '../../domain/payload/complete_setup_payload.dart';
import '../../domain/payload/discovery_settings_payload.dart';
import '../../domain/entity/interest_entity.dart';
import '../../domain/repository/profile_repository.dart';

class ProfileRepositoryImpl implements ProfileRepository {
  final ProfileDataSource remoteDataSource;

  ProfileRepositoryImpl({required this.remoteDataSource});

  @override
  Future<Either<Failure, List<InterestEntity>>> getInterests() async {
    return handleRequest(() async {
      final response = await remoteDataSource.getInterests();
      return response;
    });
  }

  @override
  Future<Either<Failure, Unit>> completeSetup(
    CompleteSetupPayload payload,
  ) async {
    return handleRequest(() async {
      await remoteDataSource.completeSetup(payload);
      return unit;
    });
  }

  @override
  Future<Either<Failure, Unit>> discoverySettings(
    DiscoverySettingsPayload payload,
  ) async {
    return handleRequest(() async {
      await remoteDataSource.discoverySettings(payload);
      return unit;
    });
  }

  @override
  Future<Either<Failure, Unit>> saveInterests(List<String> interests) async {
    return handleRequest(() async {
      await remoteDataSource.saveInterests(interests);
      return unit;
    });
  }
}
