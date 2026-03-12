part of 'auth_bloc.dart';

@immutable
sealed class AuthState {}

final class AuthenticatedState extends AuthState {
  final AuthEntity authEntity;

  AuthenticatedState({required this.authEntity});
}

final class UnAuthenticatedState extends AuthState {}

final class AuthenticationErrorState extends AuthState {
  final String message;

  AuthenticationErrorState({required this.message});
}

final class AuthenticationLoadingState extends AuthState {}
