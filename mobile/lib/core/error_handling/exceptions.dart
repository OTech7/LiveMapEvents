class ServerException implements Exception {
  ServerException({this.message});

  String? message;
}

class OfflineException implements Exception {}

class UnAuthorizedException implements Exception {}

class BlockedException implements Exception {}

class WrongDataException implements Exception {
  WrongDataException({required this.message});

  String message;
}

class WrongCredentialsException implements Exception {}

class EmptyCacheException implements Exception {}

class SomeThingWentWrongException implements Exception {}

class JsonParsingException implements Exception {}

class NotFoundException implements Exception {}
