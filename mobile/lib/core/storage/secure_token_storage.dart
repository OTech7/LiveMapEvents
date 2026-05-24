import 'package:flutter_secure_storage/flutter_secure_storage.dart';

/// Abstraction over secure key/value storage for the auth token. Kept narrow
/// on purpose so call sites do not couple to FlutterSecureStorage directly
/// and so the implementation is trivial to fake in tests.
abstract class TokenStorage {
  Future<String?> read();

  Future<void> write(String token);

  Future<void> delete();
}

/// FlutterSecureStorage-backed implementation. On Android this is configured
/// to use EncryptedSharedPreferences (Keystore-backed) so the token cannot be
/// read from a rooted device or a backup. On iOS the default Keychain
/// behaviour is already secure.
class SecureTokenStorage implements TokenStorage {
  static const String _tokenKey = 'auth_token';

  final FlutterSecureStorage _storage;

  SecureTokenStorage({FlutterSecureStorage? storage})
      : _storage =
      storage ??
          const FlutterSecureStorage(
            aOptions: AndroidOptions(encryptedSharedPreferences: true),
          );

  @override
  Future<String?> read() => _storage.read(key: _tokenKey);

  @override
  Future<void> write(String token) =>
      _storage.write(key: _tokenKey, value: token);

  @override
  Future<void> delete() => _storage.delete(key: _tokenKey);
}
