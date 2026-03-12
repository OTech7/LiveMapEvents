import 'package:dio/dio.dart';

import '../strings/failures.dart';
import 'exceptions.dart';
import 'failures.dart';

class ApiErrorHandler {
  static Failure exceptionToFailure(error) {
    if (error is DioException && error.error is Exception) {
      error = error.error;
    }
    print("\u001B[36m$error\u001B[0m");

    Failure? failure;
    switch (error) {
      case ServerException():
        failure = ServerFailure(message: error.message ?? serverFailureMessage);
        break;

      case OfflineException():
        failure = OfflineFailure();
        break;
      case UnAuthorizedException():
        failure = UnAuthorizedFailure();
        break;
      case BlockedException():
        failure = BlockedFailure();
        break;
      case WrongDataException():
        failure = WrongDataFailure(message: error.message);
        break;

      case WrongCredentialsException():
        failure = WrongCredentialsFailure();
        break;
      case EmptyCacheException():
        failure = EmptyCacheFailure();
        break;
      case JsonParsingException():
        failure = JsonParsingFailure();
        break;

      case SomeThingWentWrongException():
        failure = SomethingWentWrongFailure();
        break;
      case NotFoundException():
        failure = NotFoundFailure();
        break;
      default:
        failure = SomethingWentWrongFailure();
    }
    print("\u001B[36m$failure\u001B[0m");

    return failure;
  }
}
