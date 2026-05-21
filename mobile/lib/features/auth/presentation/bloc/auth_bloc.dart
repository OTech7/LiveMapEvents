import 'dart:async';

import 'package:flutter/cupertino.dart';
import 'package:flutter/foundation.dart' show kIsWeb;
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

  /// Subscription to the unified Google Sign-In event stream. Both the
  /// rendered Google button on web and `authenticate()` on Android/iOS
  /// surface their results here, so this is the single funnel that turns
  /// a Google account into an `ID token -> backend exchange -> AuthState`.
  StreamSubscription<GoogleSignInAuthenticationEvent>?
  _googleAuthEventsSubscription;

  AuthBloc({
    required this.loginUseCase,
    required this.registerUseCase,
    required this.checkTokenUseCase,
    required this.logoutUseCase,
    required this.verifyUseCase,
    required this.sendOTPUseCase,
    required this.signInWithGoogleUseCase,
  }) : super(UnAuthenticatedState()) {
    // Subscribe to GoogleSignIn events. `initialize()` is called once in
    // main.dart before runApp(); the stream is a broadcast stream so it's
    // safe to listen at any time after.
    _googleAuthEventsSubscription =
        GoogleSignIn.instance.authenticationEvents.listen(
              (event) {
            if (event is GoogleSignInAuthenticationEventSignIn) {
              final idToken = event.user.authentication.idToken;
              if (idToken == null || idToken.isEmpty) {
                add(GoogleSignInFailedEvent('Failed to retrieve ID token'));
              } else {
                add(GoogleIdTokenReceivedEvent(idToken));
              }
            }
            // SignOut events are emitted on disconnect / logout — handled
            // elsewhere via LogoutEvent, so nothing to do here.
          },
          onError: (Object error) {
            add(GoogleSignInFailedEvent(error.toString()));
          },
        );
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

    // Triggered by the user tapping the custom "Sign in with Google" button
    // on Android/iOS. On the web the official Google-rendered button drives
    // sign-in directly through GIS (v7's authenticate() is unsupported on
    // web), so this handler is a no-op there.
    on<SignInWithGoogleEvent>((event, emit) async {
      if (kIsWeb) {
        // The rendered Google button handles interactive sign-in on web.
        // If we somehow reach here, just surface a friendly error instead
        // of throwing UnsupportedError.
        emit(AuthenticationErrorState(
          message: 'Use the Google button to sign in.',
        ));
        return;
      }

      emit(AuthenticationLoadingState());
      try {
        // The result is also pushed to `authenticationEvents`, which the
        // stream subscription above forwards as a `GoogleIdTokenReceivedEvent`.
        // We just need to kick off the native flow here.
        await GoogleSignIn.instance.authenticate();
      } on GoogleSignInException catch (e) {
        // User cancellation, network error, plugin misconfig, etc.
        emit(AuthenticationErrorState(
          message: e.description ?? 'Google sign-in failed',
        ));
      } catch (e) {
        emit(AuthenticationErrorState(message: e.toString()));
      }
    });

    // Fired by the authenticationEvents listener for both web (rendered
    // button) and mobile (authenticate()) flows. This is where the ID token
    // actually reaches the backend.
    on<GoogleIdTokenReceivedEvent>((event, emit) async {
      emit(AuthenticationLoadingState());
      final response = await signInWithGoogleUseCase(event.idToken);
      response.fold(
        (failure) => emit(
          AuthenticationErrorState(message: mapFailureToMessage(failure)),
        ),
        (authEntity) => emit(AuthenticatedState(authEntity: authEntity)),
      );
    });

    on<GoogleSignInFailedEvent>((event, emit) {
      emit(AuthenticationErrorState(message: event.message));
    });
  }

  @override
  Future<void> close() {
    _googleAuthEventsSubscription?.cancel();
    return super.close();
  }
}
