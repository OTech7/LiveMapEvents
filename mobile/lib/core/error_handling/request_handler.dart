import 'package:dartz/dartz.dart';

import 'exception_to_failure.dart';
import 'failures.dart';

Future<Either<Failure, T>> handleRequest<T>(
  Future<T> Function() request,
) async {
  try {
    final result = await request();
    return Right(result);
  } catch (e) {
    return Left(ApiErrorHandler.exceptionToFailure(e));
  }
}
