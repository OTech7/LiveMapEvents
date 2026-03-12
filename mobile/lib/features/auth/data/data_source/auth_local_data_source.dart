import 'dart:convert';
import 'dart:developer';

import 'package:dartz/dartz.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../model/auth_model.dart';

abstract class AuthLocalDataSource {
  Future<Unit> deleteAuthFromLocal();

  Future<Unit> saveAuthToLocal(AuthModel authModel);

  Future<AuthModel?> getAuthFromLocal();
}

const String kUserKey = 'AUTH_USER';

class AuthLocalDataSourceImpl implements AuthLocalDataSource {
  final SharedPreferences sharedPreferences;

  AuthLocalDataSourceImpl({required this.sharedPreferences});

  @override
  Future<Unit> deleteAuthFromLocal() async {
    await sharedPreferences.remove(kUserKey);
    return unit;
  }

  @override
  Future<Unit> saveAuthToLocal(AuthModel authModel) async {
    final userJson = jsonEncode(authModel.toJson());
    await sharedPreferences.setString(kUserKey, userJson);
    return unit;
  }

  @override
  Future<AuthModel?> getAuthFromLocal() async {
    final jsonString = sharedPreferences.getString(kUserKey);
    if (jsonString != null) {
      final jsonMap = jsonDecode(jsonString);
      return AuthModel.fromJson(jsonMap);
    } else {
      return null;
    }
  }
}
