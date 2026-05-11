import 'package:bloc/bloc.dart';
import 'package:meta/meta.dart';

import '../../../../core/error_handling/failure_to_message.dart';
import '../../domain/entity/interest_entity.dart';
import '../../domain/payload/complete_setup_payload.dart';
import '../../domain/payload/discovery_settings_payload.dart';
import '../../domain/use_case/complete_setup_usecase.dart';
import '../../domain/use_case/discovery_settings_usecase.dart';
import '../../domain/use_case/save_interests_usecase.dart';
import '../../domain/use_case/get_interests_usecase.dart';

part 'profile_event.dart';

part 'profile_state.dart';

class ProfileBloc extends Bloc<ProfileEvent, ProfileState> {
  GetInterestsUseCase getInterestsUseCase;
  CompleteSetupUseCase completeSetupUseCase;
  DiscoverySettingsUseCase discoverySettingsUseCase;
  SaveInterestsUseCase saveInterestsUseCase;

  ProfileBloc({
    required this.getInterestsUseCase,
    required this.completeSetupUseCase,
    required this.discoverySettingsUseCase,
    required this.saveInterestsUseCase,
  }) : super(ProfileInitialState()) {
    on<GetInterestsEvent>((event, emit) async {
      emit(ProfileLoadingState());
      final response = await getInterestsUseCase();
      response.fold(
        (failure) =>
            emit(ProfileErrorState(message: mapFailureToMessage(failure))),
        (interests) => emit(InterestsLoadedState(interests)),
      );
    });

    on<CompleteSetupEvent>((event, emit) async {
      emit(ProfileLoadingState());
      final response = await completeSetupUseCase(event.payload);
      response.fold(
        (failure) =>
            emit(ProfileErrorState(message: mapFailureToMessage(failure))),
        (_) => emit(SetupCompletedState()),
      );
    });

    on<DiscoverySettingsEvent>((event, emit) async {
      emit(ProfileLoadingState());
      final response = await discoverySettingsUseCase(event.payload);
      response.fold(
        (failure) =>
            emit(ProfileErrorState(message: mapFailureToMessage(failure))),
        (_) => emit(DiscoverySettingsSuccessState()),
      );
    });

    on<SaveInterestsEvent>((event, emit) async {
      emit(ProfileLoadingState());
      final response = await saveInterestsUseCase(event.interests);
      response.fold(
        (failure) =>
            emit(ProfileErrorState(message: mapFailureToMessage(failure))),
        (_) => emit(SetupCompletedState()),
      );
    });
  }
}
