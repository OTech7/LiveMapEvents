import 'user_entity.dart';

class AuthEntity {
  String token;
  String refreshToken;
  bool profileComplete;
  bool interestsComplete;
  bool discoverySettingsComplete;
  UserEntity? user;

  AuthEntity({
    required this.token,
    required this.refreshToken,
    this.profileComplete = false,
    this.interestsComplete = false,
    this.discoverySettingsComplete = false,
    this.user,
  });
}
