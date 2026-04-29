import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile/features/auth/presentation/pages/onboarding/onboarding_screen.dart';
import 'package:mobile/features/profile/presentation/bloc/profile_bloc.dart';
import '/features/auth/presentation/bloc/auth_bloc.dart';
import '/features/auth/presentation/pages/login/login_screen.dart';
import '/features/auth/presentation/pages/register/register_screen.dart';
import '/features/auth/presentation/pages/verification/verification_screen.dart';
import '../../features/profile/presentation/pages/profile_setup/set_up_profile_screen.dart';
import '../../features/profile/presentation/pages/personalize_feed/personalize_feed_screen.dart';
import 'package:mobile/core/dependency_injection/injection.dart' as di;

final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();

class AppRouter {
  AppRouter._();

  static final GoRouter router = GoRouter(
    initialLocation: '/',
    navigatorKey: navigatorKey,
    routes: [
      GoRoute(
        path: '/',
        name: 'onboarding',
        builder: (context, state) => OnboardingScreen(),
      ),
      GoRoute(
        path: '/login',
        name: 'login',
        builder: (context, state) => LoginScreen(),
      ),
      GoRoute(
        path: '/register',
        name: 'register',
        builder: (context, state) => BlocProvider(
          create: (context) => di.sl<AuthBloc>(),
          child: RegisterScreen(),
        ),
      ),
      GoRoute(
        path: '/verification_screen',
        name: 'verification_screen',
        builder: (context, state) {
          final extra = state.extra as Map<String, dynamic>?;
          return BlocProvider(
            create: (context) => di.sl<AuthBloc>(),
            child: VerificationScreen(
              phoneNumber: extra?['phoneNumber'] ?? '',
            ),
          );
        },
      ),
      GoRoute(
        path: '/set_up_profile',
        name: 'set_up_profile',
        builder: (context, state) => BlocProvider(
          create: (context) => di.sl<ProfileBloc>(),
          child: SetUpProfileScreen(),
        ),
      ),
      GoRoute(
        path: '/personalize_feed',
        name: 'personalize_feed',
        builder: (context, state) {
          final extra = state.extra as Map<String, dynamic>?;
          return BlocProvider(
            create: (context) => di.sl<ProfileBloc>(),
            child: PersonalizeFeedScreen(
              firstName: extra?['firstName'] ?? '',
              lastName: extra?['lastName'] ?? '',
              phone: extra?['phone'] ?? '',
              gender: extra?['gender'] ?? 'male',
              dob: extra?['dob'] ?? '',
              lat: extra?['lat'] ?? 0.0,
              lng: extra?['lng'] ?? 0.0,
            ),
          );
        },
      ),
    ],
  );
}
