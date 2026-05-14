import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../features/auth/domain/entity/auth_entity.dart';

class NavigationHelper {
  static void handleAuthNavigation(BuildContext context, AuthEntity auth) {
    if (!auth.profileComplete) {
      context.push('/set_up_profile');
    } else if (!auth.discoverySettingsComplete) {
      context.push(
        '/discovery_settings',
        extra: {
          'firstName': auth.user?.firstName,
          'lastName': auth.user?.lastName,
          'phone': auth.user?.phone,
          'gender': auth.user?.gender,
          'dob': auth.user?.dob,
          'lat': auth.user?.lat,
          'lng': auth.user?.lng,
        },
      );
    } else if (!auth.interestsComplete) {
      context.push(
        '/personalize_feed',
        extra: {
          'firstName': auth.user?.firstName,
          'lastName': auth.user?.lastName,
          'phone': auth.user?.phone,
          'gender': auth.user?.gender,
          'dob': auth.user?.dob,
          'lat': auth.user?.lat,
          'lng': auth.user?.lng,
        },
      );
    } else {
      context.go('/nav_screen');
    }
  }
}
