import 'package:flutter/material.dart';
import 'package:google_sign_in_web/web_only.dart' as web;

/// Web-only helper that returns the official Google-rendered button.
/// On v7+ this is the ONLY supported interactive sign-in path on the web —
/// GoogleSignIn.authenticate() throws UnsupportedError, and a custom
/// button can't trigger the GIS popup. The returned widget handles its
/// own click; the result reaches AuthBloc through
/// GoogleSignIn.instance.authenticationEvents.
///
/// Styled to match Google's standard "Sign in with Google" pill button:
/// outline theme (white background + grey border), pill shape, large
/// size, English locale, "Sign in with Google" text on the right of the
/// Google "G" mark. The whole pill is one click target.
///
/// Note: when a visitor already has an active Google session, GIS may
/// replace the generic text with a personalized chip ("Sign in as ..."
/// + the user's email). That's a built-in GIS behavior bundled with
/// type=standard and cannot be disabled. Visitors who aren't signed in
/// to Google in their browser see the generic English text exactly as
/// configured below.
Widget buildGoogleRenderedButton() {
  return web.renderButton(
    configuration: web.GSIButtonConfiguration(
      type: web.GSIButtonType.standard,
      theme: web.GSIButtonTheme.outline,
      size: web.GSIButtonSize.large,
      text: web.GSIButtonText.signinWith,
      shape: web.GSIButtonShape.pill,
      logoAlignment: web.GSIButtonLogoAlignment.left,
      locale: 'en',
    ),
  );
}
