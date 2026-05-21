import 'dart:io';
import 'package:flutter/foundation.dart' show kIsWeb;

import 'package:easy_localization/easy_localization.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:google_maps_flutter_android/google_maps_flutter_android.dart';
import 'package:google_maps_flutter_platform_interface/google_maps_flutter_platform_interface.dart';
import 'package:google_sign_in/google_sign_in.dart';
import 'core/config/env_vars.dart';
import 'core/dependency_injection/injection.dart' as di;
import 'core/app_router/app_router.dart';
import 'core/helper/app_bloc_observer.dart';
import 'core/theme/theme.dart';
import 'features/auth/presentation/bloc/auth_bloc.dart';
import 'package:flutter_dotenv/flutter_dotenv.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final GoogleMapsFlutterPlatform mapsImplementation =
      GoogleMapsFlutterPlatform.instance;
  if (mapsImplementation is GoogleMapsFlutterAndroid) {
    mapsImplementation.useAndroidViewSurface = true;
  }
  await EasyLocalization.ensureInitialized();
  if (!kIsWeb) await dotenv.load(fileName: ".env");

  // ── Initialize Google Sign-In once for the whole app lifetime. ──
  // v7 requires this before authenticate() / renderButton() can be used.
  // On Web, `clientId` is the Web OAuth client ID used by GIS in the
  // browser (the ID token's `aud` will equal this value, which is what
  // the Laravel backend verifies against GOOGLE_CLIENT_ID).
  // On Android/iOS, `serverClientId` asks Google to issue an ID token
  // whose `aud` is the *Web* client ID so the same backend verification
  // works. The native client_id is picked up automatically from
  // google-services.json / Info.plist on those platforms.
  if (EnvVars.hasGoogleServerClientId) {
    if (kIsWeb) {
      await GoogleSignIn.instance.initialize(
        clientId: EnvVars.googleServerClientId,
      );
    } else {
      await GoogleSignIn.instance.initialize(
        serverClientId: EnvVars.googleServerClientId,
      );
    }
  }

  await di.init();
  Bloc.observer = MyBlocObserver();
  SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
    DeviceOrientation.portraitDown,
  ]);

  HttpOverrides.global = MyHttpOverrides();

  runApp(
    EasyLocalization(
      supportedLocales: const [Locale('en'), Locale('ar'), Locale('de')],
      path: 'assets/language',
      startLocale: Locale('en'),
      child: const MyApp(),
    ),
  );
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (context) => di.sl<AuthBloc>(),
      child: MaterialApp.router(
        title: 'Event Map',
        localizationsDelegates: context.localizationDelegates,
        supportedLocales: context.supportedLocales,
        locale: context.locale,
        debugShowCheckedModeBanner: false,
        routerConfig: AppRouter.router,
        theme: AppTheme.theme,
      ),
    );
  }
}

class MyHttpOverrides extends HttpOverrides {
  @override
  HttpClient createHttpClient(SecurityContext? context) {
    return super.createHttpClient(context)
      ..badCertificateCallback =
          (X509Certificate cert, String host, int port) => true;
  }
}
