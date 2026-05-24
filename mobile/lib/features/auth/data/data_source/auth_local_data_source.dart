import 'dart:convert';

import 'package:dartz/dartz.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../../core/network/token_provider.dart';
import '../../../../core/storage/secure_token_storage.dart';
import '../model/auth_model.dart';

abstract class AuthLocalDataSource {
  Future<Unit> deleteAuthFromLocal();

  Future<Unit> saveAuthToLocal(AuthModel authModel);

  Future<AuthModel?> getAuthFromLocal();
}

const String kUserKey = 'AUTH_USER';

class AuthLocalDataSourceImpl
    implements AuthLocalDataSource, AuthTokenProvider {
  final SharedPreferences sharedPreferences;
  final TokenStorage tokenStorage;

  AuthLocalDataSourceImpl({
    required this.sharedPreferences,
    required this.tokenStorage,
  });

  @override
  Future<Unit> deleteAuthFromLocal() async {
    await sharedPreferences.remove(kUserKey);
    await tokenStorage.delete();
    return unit;
  }

  @override
  Future<Unit> saveAuthToLocal(AuthModel authModel) async {
    // Persist the bearer token in the OS-backed secure store (Keychain on
    // iOS, EncryptedSharedPreferences on Android) instead of plain
    // SharedPreferences. The remaining fields stay in SharedPreferences
    // because they are non-secret session flags.
    await tokenStorage.write(authModel.token);
    final userJson = jsonEncode(authModel.toJson());
    await sharedPreferences.setString(kUserKey, userJson);
    return unit;
  }

  @override
  Future<AuthModel?> getAuthFromLocal() async {
    final jsonString = sharedPreferences.getString(kUserKey);
    if (jsonString == null) return null;
    final jsonMap = jsonDecode(jsonString) as Map<String, dynamic>;
    // Overlay the secure-storage token onto the persisted JSON so callers
    // always see a single, consistent AuthModel.
    final secureToken = await tokenStorage.read();
    if (secureToken != null) {
      jsonMap['token'] = secureToken;
    }
    return AuthModel.fromJson(jsonMap);
  }

  @override
  Future<void> deleteUser() async {
    await sharedPreferences.remove(kUserKey);
    await tokenStorage.delete();
  }

  @override
  Future<String?> getToken() => tokenStorage.read();
}
