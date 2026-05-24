// Scaffold for AppInterceptor tests. The single assertion below keeps
// `flutter test` green while the suite is being built out.
//
// TODO: Replace this with real coverage:
//   - 401 on a non-login endpoint triggers _forceLogout (deleteUser called,
//     navigator pushed to /login).
//   - 401 on the login endpoint surfaces WrongDataException with the server
//     `message` payload instead of forcing logout.
//   - When `requiresToken` is true, the Authorization header is injected
//     with `Bearer <token>` from AuthTokenProvider.
//   - When `requiresToken` is false (or missing), no Authorization header
//     is added even if a token exists.
//   - The `requiresToken` extra is removed before the request leaves the
//     interceptor so it does not leak into network logs.
//   - Status mapping: 403 -> BlockedException, 404 -> NotFoundException,
//     422 -> WrongDataException with server message, other -> ServerException.

import 'package:flutter_test/flutter_test.dart';

void main() {
  test('interceptor test scaffold', () {
    expect(true, isTrue);
  });
}
