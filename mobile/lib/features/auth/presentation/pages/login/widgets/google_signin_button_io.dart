import 'package:flutter/material.dart';

/// Non-web stub for the Google-rendered button. The widget is never
/// actually shown on Android/iOS — the custom OutlinedButton in
/// LoginWithGoogleWidget drives sign-in via GoogleSignIn.authenticate().
/// This file exists only so that the conditional import in
/// login_with_google_widget.dart resolves on non-web targets without
/// pulling in package:google_sign_in_web (which is web-only).
Widget buildGoogleRenderedButton() => const SizedBox.shrink();
