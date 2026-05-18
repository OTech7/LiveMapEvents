import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter_dotenv/flutter_dotenv.dart';

class EnvVars {
  // On web: values are baked into main.dart.js at build time via --dart-define
  // (never exposed as a downloadable file).
  // On mobile: values are loaded at runtime from the .env asset file.
  static String get googleServerClientId =>
      kIsWeb
          ? const String.fromEnvironment('GOOGLE_SERVER_CLIENT_ID')
          : dotenv.env['GOOGLE_SERVER_CLIENT_ID'] ?? '';

  static String get baseUrl =>
      kIsWeb
          ? const String.fromEnvironment(
        'BASE_URL',
        defaultValue: 'https://api.live-events-map.tech/api/v1/',
      )
          : dotenv.env['BASE_URL'] ??
          'https://api.live-events-map.tech/api/v1/';

  static bool get hasGoogleServerClientId => googleServerClientId.isNotEmpty;
}
