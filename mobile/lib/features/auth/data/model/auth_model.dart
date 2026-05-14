import 'package:equatable/equatable.dart';

import '../../domain/entity/auth_entity.dart';

import 'user_model.dart';

class AuthModel extends AuthEntity {
  AuthModel({
    required super.token,
    required super.refreshToken,
    super.profileComplete,
    super.interestsComplete,
    super.discoverySettingsComplete,
    super.user,
  });

  factory AuthModel.fromJson(Map<String, dynamic> map) {
    return AuthModel(
      refreshToken: map["refreshToken"] ?? '',
      token: map["token"] ?? '',
      profileComplete: map["profile_complete"] ?? false,
      interestsComplete: map["interests_complete"] ?? false,
      discoverySettingsComplete: map["discovery_settings_complete"] ?? false,
      user: map["user"] != null ? UserModel.fromJson(map["user"]) : null,
    );
  }

  Map<String, dynamic> toJson() => {
    "token": token,
    "refreshToken": refreshToken,
    "profile_complete": profileComplete,
    "interests_complete": interestsComplete,
    "discovery_settings_complete": discoverySettingsComplete,
  };

  @override
  List<Object?> get props => [
        token,
        refreshToken,
        profileComplete,
        interestsComplete,
        discoverySettingsComplete,
      ];
}
