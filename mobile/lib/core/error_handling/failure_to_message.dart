import '../strings/failures.dart';
import 'failures.dart';

String mapFailureToMessage(Failure failure) {
  switch (failure) {
    case ServerFailure _:
      return failure.message ?? serverFailureMessage;

    case EmptyCacheFailure _:
      return emptyCacheFailureMessage;

    case NotFoundFailure _:
      return notFoundFailureMessage;

    case OfflineFailure _:
      return offlineFailureMessage;

    case WrongCredentialsFailure _:
      return wrongCredentialsFailureMessage;

    case UnAuthorizedFailure _:
      return unAuthorizedFailureMessage;

    case BlockedFailure _:
      return blockedFailureMessage;

    case JsonParsingFailure _:
      return jsonParsingFailureMessage;

    case WrongDataFailure _:
      return failure.message;

    default:
      return someThingWentWrongMessage;
  }
}
