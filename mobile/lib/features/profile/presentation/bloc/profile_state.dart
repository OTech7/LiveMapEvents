part of 'profile_bloc.dart';

@immutable
sealed class ProfileState {}

final class ProfileInitialState extends ProfileState {}

final class SetupCompletedState extends ProfileState {}

final class DiscoverySettingsSuccessState extends ProfileState {}

final class ProfileLoadingState extends ProfileState {}

final class InterestsLoadedState extends ProfileState {
  final List<InterestEntity> interests;

  InterestsLoadedState(this.interests);
}

final class ProfileErrorState extends ProfileState {
  ProfileErrorState({required this.message});

  final String message;
}
