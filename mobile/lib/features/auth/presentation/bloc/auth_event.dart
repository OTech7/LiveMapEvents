part of 'auth_bloc.dart';

@immutable
sealed class AuthEvent {}

class RegisterEvent extends AuthEvent {
  final RegisterPayload payload;

  RegisterEvent(this.payload);
}

final class LoginEvent extends AuthEvent {
  final LoginPayload payload;

  LoginEvent(this.payload);
}

class LogoutEvent extends AuthEvent {}

final class CheckTokenEvent extends AuthEvent {}

class VerifyEvent extends AuthEvent {
  final String code;
  final String phoneNumber;

  VerifyEvent({required this.code, required this.phoneNumber});
}

class SendOTPEvent extends AuthEvent {
  final String phoneNumber;

  SendOTPEvent(this.phoneNumber);
}

class SignInWithGoogleEvent extends AuthEvent {}

/// Internal event dispatched by the bloc itself when the
/// `GoogleSignIn.authenticationEvents` stream emits a successful sign-in.
/// This is the single entry point for finishing the Google flow on every
/// platform: the rendered Google button on web and `authenticate()` on
/// Android/iOS both surface here.
class GoogleIdTokenReceivedEvent extends AuthEvent {
  final String idToken;

  GoogleIdTokenReceivedEvent(this.idToken);
}

/// Internal event for surfacing errors raised by the Google Sign-In SDK
/// (cancelled popups, network errors, missing ID token, etc.) into the
/// normal AuthState error pipeline.
class GoogleSignInFailedEvent extends AuthEvent {
  final String message;

  GoogleSignInFailedEvent(this.message);
}
