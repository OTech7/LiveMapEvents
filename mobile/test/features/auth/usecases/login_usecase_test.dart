// Scaffold for LoginUseCase tests.
//
// TODO: Replace this with real coverage:
//   - LoginUseCase.call delegates the supplied LoginPayload to
//     AuthRepository.login and returns its Either result unchanged.
//   - On Right(AuthEntity) the token-bearing AuthEntity is propagated to
//     the caller (no transformation here).
//   - On Left(Failure) the failure is propagated as-is.
//   - The repository is invoked exactly once per call (no retries inside
//     the use case).
//
// Use mocktail's Mock + when()/verify() against AuthRepository.

import 'package:flutter_test/flutter_test.dart';

void main() {
  test('login usecase test scaffold', () {
    expect(true, isTrue);
  });
}
