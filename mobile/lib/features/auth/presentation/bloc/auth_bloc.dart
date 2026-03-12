import 'package:flutter/cupertino.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/error_handling/failure_to_message.dart';
import '../../domain/entity/auth_entity.dart';
import '../../domain/payload/login_payload.dart';
import '../../domain/payload/register_payload.dart';
import '../../domain/use_case/checkTokenUseCase.dart';
import '../../domain/use_case/login_usecase.dart';
import '../../domain/use_case/logout_usecase.dart';
import '../../domain/use_case/register_usecase.dart';


part 'auth_event.dart';
part 'auth_state.dart';

class AuthBloc extends Bloc<AuthEvent, AuthState> {
  LoginUseCase loginUseCase;
  RegisterUseCase registerUseCase;
  LogoutUseCase logoutUseCase;
  CheckTokenUseCase checkTokenUseCase;

  AuthBloc({
    required this.loginUseCase,
    required this.registerUseCase,
    required this.checkTokenUseCase,
    required this.logoutUseCase,
  }) : super(UnAuthenticatedState()) {
    on<LoginEvent>((event, emit) async {
      emit(AuthenticationLoadingState());
      final response = await loginUseCase(event.payload);
      response.fold(
          (failure) => {
                emit(AuthenticationErrorState(
                    message: mapFailureToMessage(failure)))
              }, (auth) {
        emit(AuthenticatedState(authEntity: auth));
      });
    });
    on<RegisterEvent>((event, emit) async {
      emit(AuthenticationLoadingState());
      final response = await registerUseCase(event.payload);
      response.fold(
          (failure) => {
                emit(AuthenticationErrorState(
                    message: mapFailureToMessage(failure)))
              }, (auth) {
        emit(AuthenticatedState(authEntity: auth));
      });
    });
    on<LogoutEvent>((event, emit) async {
      final response = await logoutUseCase();
      response.fold(
          (failure) => {
                emit(AuthenticationErrorState(
                    message: mapFailureToMessage(failure)))
              }, (airports) {
        emit(UnAuthenticatedState());
      });
    });
    on<CheckTokenEvent>((event, emit) async {
      final response = await checkTokenUseCase();
      response.fold((failure) {
        emit(AuthenticationErrorState(message: mapFailureToMessage(failure)));
      }, (authEntity) {
        if (authEntity != null) {
          emit(AuthenticatedState(authEntity: authEntity));
        } else {
          emit(UnAuthenticatedState());
        }
      });
    });
  }
}
