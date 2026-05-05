part of 'profile_bloc.dart';

@immutable
sealed class ProfileEvent {}

class GetInterestsEvent extends ProfileEvent {}

class CompleteSetupEvent extends ProfileEvent {
  final CompleteSetupPayload payload;

  CompleteSetupEvent(this.payload);
}

class DiscoverySettingsEvent extends ProfileEvent {
  final DiscoverySettingsPayload payload;

  DiscoverySettingsEvent(this.payload);
}

class SaveInterestsEvent extends ProfileEvent {
  final List<String> interests;

  SaveInterestsEvent(this.interests);
}
