abstract class AuthTokenProvider {
  Future<String?> getToken();

  Future<void> deleteUser();
}
