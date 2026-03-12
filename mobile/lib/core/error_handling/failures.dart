import 'package:equatable/equatable.dart';

abstract class Failure extends Equatable {}

class OfflineFailure extends Failure {
  @override
  List<Object?> get props => [];
}

class ServerFailure extends Failure {
  ServerFailure({required this.message});

  final String? message;

  @override
  List<Object?> get props => [message];
}

class UnAuthorizedFailure extends Failure {
  @override
  List<Object?> get props => [];
}

class BlockedFailure extends Failure {
  @override
  List<Object?> get props => [];
}

class WrongDataFailure extends Failure {
  WrongDataFailure({required this.message});

  final String message;

  @override
  List<Object?> get props => [message];
}

class WrongCredentialsFailure extends Failure {
  @override
  List<Object?> get props => [];
}

class EmptyCacheFailure extends Failure {
  @override
  List<Object?> get props => [];
}

class SomethingWentWrongFailure extends Failure {
  @override
  List<Object?> get props => [];
}

class JsonParsingFailure extends Failure {
  @override
  List<Object?> get props => [];
}

class NotFoundFailure extends Failure {
  @override
  List<Object?> get props => [];
}
