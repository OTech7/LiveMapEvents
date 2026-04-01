part of 'profile_bloc.dart';

@immutable
sealed class ProfileEvent {}

class GetInterestsEvent extends ProfileEvent {}

class CompleteSetupEvent extends ProfileEvent {
  final CompleteSetupPayload payload;
  CompleteSetupEvent(this.payload);
}