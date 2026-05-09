import 'dart:async';

import 'package:flutter/cupertino.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:mobile/features/auth/domain/use_case/send_otp_usecase.dart';

import '../../../../core/error_handling/failure_to_message.dart';
import 'package:google_sign_in/google_sign_in.dart';
import '../../domain/entity/auth_entity.dart';
import '../../domain/payload/login_payload.dart';
import '../../domain/payload/register_payload.dart';
import '../../domain/payload/verify_payload.dart';
import '../../domain/use_case/check_token_usecase.dart';
import '../../domain/use_case/login_usecase.dart';
import '../../domain/use_case/logout_usecase.dart';
import '../../domain/use_case/register_usecase.dart';
import '../../domain/use_case/verify_usecase.dart';
import '../../domain/use_case/sign_in_with_google_usecase.dart';

part 'auth_event.dart';

part 'auth_state.dart';

class AuthBloc extends Bloc<AuthEvent, AuthState> {
  LoginUseCase loginUseCase;
  RegisterUseCase registerUseCase;
  LogoutUseCase logoutUseCase;
  CheckTokenUseCase checkTokenUseCase;
  VerifyUseCase verifyUseCase;
  SendOTPUseCase sendOTPUseCase;
  SignInWithGoogleUseCase signInWithGoogleUseCase;

  AuthBloc({
    required this.loginUseCase,
    required this.registerUseCase,
    required this.checkTokenUseCase,
    required this.logoutUseCase,
    required this.verifyUseCase,
    required this.sendOTPUseCase,
    required this.signInWithGoogleUseCase,
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
      final payload = VerifyPayload(
        phoneNumber: event.phoneNumber,
        code: event.code,
      );
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

    on<SendOTPEvent>((event, emit) async {
      emit(AuthenticationLoadingState());
      final response = await sendOTPUseCase(event.phoneNumber);
      response.fold(
        (failure) {
          emit(AuthenticationErrorState(message: mapFailureToMessage(failure)));
        },
        (auth) {
          emit(OTPSentSuccessfullyState(phoneNumber: event.phoneNumber));
        },
      );
    });

    on<SignInWithGoogleEvent>((event, emit) async {
      emit(AuthenticationLoadingState());

      try {
        final googleSignIn = GoogleSignIn.instance;

        await googleSignIn.initialize(
          serverClientId:
              '103431306679-m3dg292f9e0a71oj4ed1ts9nljsrbt7r.apps.googleusercontent.com',
        );

        final account = await googleSignIn.authenticate();

        if (account == null) {
          emit(AuthenticationErrorState(message: 'Google Sign In Cancelled'));
          return;
        }

        final auth = await account.authentication;
        final idToken = auth.idToken;

        if (idToken == null) {
          emit(AuthenticationErrorState(message: 'Failed to retrieve ID token'));
          return;
        }

        final response = await signInWithGoogleUseCase(idToken);

        response.fold(
          (failure) => emit(
            AuthenticationErrorState(message: mapFailureToMessage(failure)),
          ),
          (authEntity) => emit(AuthenticatedState(authEntity: authEntity)),
        );
      } catch (e) {
        emit(AuthenticationErrorState(message: e.toString()));
      }
    });
  }
}
