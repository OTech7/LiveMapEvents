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
