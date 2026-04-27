import 'package:equatable/equatable.dart';

import '../../domain/entity/auth_entity.dart';

class AuthModel extends AuthEntity {
  AuthModel({
    required super.token,
    required super.refreshToken,
    super.profileComplete,
  });

  factory AuthModel.fromJson(Map<String, dynamic> map) {
    return AuthModel(
      refreshToken: map["refreshToken"] ?? '',
      token: map["token"] ?? '',
      profileComplete: map["profile_complete"] ?? false,
    );
  }

  Map<String, dynamic> toJson() => {
        "token": token,
        "refreshToken": refreshToken,
        "profile_complete": profileComplete,
      };

  @override
  List<Object?> get props => [token, refreshToken, profileComplete];
}
