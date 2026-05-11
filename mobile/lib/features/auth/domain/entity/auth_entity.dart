class AuthEntity {
  String token;
  String refreshToken;
  bool profileComplete;

  AuthEntity({
    required this.token,
    required this.refreshToken,
    this.profileComplete = false,
  });
}
