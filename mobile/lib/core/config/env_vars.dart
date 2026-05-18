import 'package:flutter_dotenv/flutter_dotenv.dart';

class EnvVars {
  static String get googleServerClientId =>
      dotenv.env['GOOGLE_SERVER_CLIENT_ID'] ?? '';

  static String get baseUrl =>
      dotenv.env['BASE_URL'] ?? 'https://api.live-events-map.tech/api/v1/';

  static bool get hasGoogleServerClientId => googleServerClientId.isNotEmpty;
}
