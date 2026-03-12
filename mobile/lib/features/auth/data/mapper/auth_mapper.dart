import '../../domain/entity/auth_entity.dart';
import '../model/auth_model.dart';

class AuthMapper {
  AuthEntity toEntity(AuthModel model) {
    return AuthEntity(refreshToken: model.refreshToken, token: model.token);
  }

  AuthModel toModel(AuthEntity entity) {
    return AuthModel(refreshToken: entity.refreshToken, token: entity.token);
  }
}
