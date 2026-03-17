import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile/features/auth/presentation/pages/onboarding/onboarding_screen.dart';
import '/features/auth/presentation/bloc/auth_bloc.dart';
import '/features/auth/presentation/pages/login/login_screen.dart';
import '/features/auth/presentation/pages/register/register_screen.dart';
import '/features/auth/presentation/pages/verification/verification_screen.dart';
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
        builder: (context, state) =>
            BlocProvider(
              create: (context) => di.sl<AuthBloc>(),
              child: RegisterScreen(),
            ),
      ),
      GoRoute(
        path: '/verification_screen',
        name: 'verification_screen',
        builder: (context, state) =>
            BlocProvider(
              create: (context) => di.sl<AuthBloc>(),
              child: VerificationScreen(),
            ),
      ),
    ],
  );
}
