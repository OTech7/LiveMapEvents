import 'package:equatable/equatable.dart';

class AuthModel extends Equatable {
  String token;
  String refreshToken;

  AuthModel({
    required this.token,
    required this.refreshToken,
  });

  factory AuthModel.fromJson(Map<String, dynamic> map) {

    return AuthModel(
        refreshToken: map["refreshToken"],
        token: map["token"]);
  }

  Map<String, dynamic> toJson() => {
        "token": token,
        "refreshToken": refreshToken,
      };

  @override
  List<Object?> get props => [token, refreshToken];
}
