import 'package:dartz/dartz.dart';
import '../../../../core/error_handling/failures.dart';
import '../payload/discovery_settings_payload.dart';
import '../repository/profile_repository.dart';

class DiscoverySettingsUseCase {
  final ProfileRepository repository;

  DiscoverySettingsUseCase(this.repository);

  Future<Either<Failure, Unit>> call(DiscoverySettingsPayload payload) {
    return repository.discoverySettings(payload);
  }
}
