import 'package:dartz/dartz.dart';
import '../../../../core/error_handling/failures.dart';
import '../repository/auth_repository.dart';

class SendOTPUseCase {
  AuthRepository repository;

  SendOTPUseCase({required this.repository});

  Future<Either<Failure, Unit>> call(String phoneNumber) async {
    return await repository.sendOTP(phoneNumber);
  }
}
