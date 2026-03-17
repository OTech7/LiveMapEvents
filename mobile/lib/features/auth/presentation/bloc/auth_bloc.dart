import 'dart:async';

import 'package:flutter/cupertino.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../core/error_handling/failure_to_message.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../domain/entity/auth_entity.dart';
import '../../domain/payload/login_payload.dart';
import '../../domain/payload/register_payload.dart';
import '../../domain/use_case/checkTokenUseCase.dart';
import '../../domain/use_case/login_usecase.dart';
import '../../domain/use_case/logout_usecase.dart';
import '../../domain/use_case/register_usecase.dart';
import '../../domain/use_case/verify_usecase.dart';
import '../../domain/payload/verify_payload.dart';

part 'auth_event.dart';
part 'auth_state.dart';

class AuthBloc extends Bloc<AuthEvent, AuthState> {
  LoginUseCase loginUseCase;
  RegisterUseCase registerUseCase;
  LogoutUseCase logoutUseCase;
  CheckTokenUseCase checkTokenUseCase;
  VerifyUseCase verifyUseCase;

  AuthBloc({
    required this.loginUseCase,
    required this.registerUseCase,
    required this.checkTokenUseCase,
    required this.logoutUseCase,
    required this.verifyUseCase,
  }) : super(UnAuthenticatedState()) {
    on<LoginEvent>((event, emit) async {
      emit(AuthenticationLoadingState());
      final response = await loginUseCase(event.payload);
      response.fold(
        (failure) => {
          emit(AuthenticationErrorState(message: mapFailureToMessage(failure))),
        },
        (auth) {
          emit(AuthenticatedState(authEntity: auth));
        },
      );
    });
    on<RegisterEvent>((event, emit) async {
      emit(AuthenticationLoadingState());
      final response = await registerUseCase(event.payload);
      response.fold(
        (failure) => {
          emit(AuthenticationErrorState(message: mapFailureToMessage(failure))),
        },
        (auth) {
          emit(AuthenticatedState(authEntity: auth));
        },
      );
    });
    on<LogoutEvent>((event, emit) async {
      final response = await logoutUseCase();
      response.fold(
        (failure) => {
          emit(AuthenticationErrorState(message: mapFailureToMessage(failure))),
        },
        (airports) {
          emit(UnAuthenticatedState());
        },
      );
    });
    on<CheckTokenEvent>((event, emit) async {
      final response = await checkTokenUseCase();
      response.fold(
        (failure) {
          emit(AuthenticationErrorState(message: mapFailureToMessage(failure)));
        },
        (authEntity) {
          if (authEntity != null) {
            emit(AuthenticatedState(authEntity: authEntity));
          } else {
            emit(UnAuthenticatedState());
          }
        },
      );
    });

    on<VerifyEvent>((event, emit) async {
      emit(AuthenticationLoadingState());
      final payload = VerifyPayload(code: event.code);
      final response = await verifyUseCase(payload);
      response.fold(
        (failure) {
          emit(AuthenticationErrorState(message: mapFailureToMessage(failure)));
        },
        (auth) {
          emit(AuthenticatedState(authEntity: auth));
        },
      );
    });

    on<SignInWithGoogleEvent>((event, emit) async {
      emit(AuthenticationLoadingState());
      try {
        final GoogleSignIn signIn = GoogleSignIn.instance;
        // unawaited(
        //   signIn.initialize(clientId: clientId, serverClientId: serverClientId).then((
        //       _,
        //       ) {
        //     signIn.authenticationEvents
        //         .listen(_handleAuthenticationEvent)
        //         .onError(_handleAuthenticationError);
        /// This example always uses the stream-based approach to determining
        /// which UI state to show, rather than using the future returned here,
        /// if any, to conditionally skip directly to the signed-in state.
        // signIn.attemptLightweightAuthentication();
        // }),
        // );
        // final GoogleSignInAccount? googleUser = await googleSignIn.signIn();
        // if (googleUser != null) {
        //   final SharedPreferences sp = await SharedPreferences.getInstance();
        //   await sp.setString('google_email', googleUser.email);
        //   TODO: authenticate with backend
        //   Here we just stop loading; you would emit AuthenticatedState on success
        // } else {
        //   emit(AuthenticationErrorState(message: 'Google Sign In Cancelled'));
        // }
      } catch (e) {
        emit(AuthenticationErrorState(message: e.toString()));
      }
    });
  }
}
