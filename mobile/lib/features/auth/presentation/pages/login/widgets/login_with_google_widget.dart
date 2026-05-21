import 'package:flutter/foundation.dart' show kIsWeb;
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../../../core/strings/app_strings.dart';
import '../../../bloc/auth_bloc.dart';

// Conditional import: on web we pull in the package:google_sign_in_web
// `renderButton()` helper; on mobile/desktop we use a no-op stub so we
// don't try to compile web-only code.
import 'google_signin_button_io.dart'
if (dart.library.js_interop) 'google_signin_button_web.dart';

class LoginWithGoogleWidget extends StatelessWidget {
  const LoginWithGoogleWidget({super.key, required this.size});

  final Size size;

  @override
  Widget build(BuildContext context) {
    // On web, the Google-rendered button is the only supported interactive
    // sign-in path in google_sign_in v7. It handles its own click and
    // surfaces the signed-in account through
    // GoogleSignIn.instance.authenticationEvents — which AuthBloc already
    // listens to. The standard GIS pill button has the "Sign in with
    // Google" text + G icon built in, so the whole pill is one click
    // target — no need for an extra label or click hack.
    if (kIsWeb) {
      return Center(child: buildGoogleRenderedButton());
    }

    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        return OutlinedButton.icon(
          onPressed: () {
            context.read<AuthBloc>().add(SignInWithGoogleEvent());
          },
          icon: const Icon(Icons.g_mobiledata, color: Colors.red, size: 30),
          label: Text(
            AppStrings.signInWithGoogle,
            style: Theme.of(context).textTheme.bodyLarge,
          ),
          style: OutlinedButton.styleFrom(
            padding: EdgeInsets.symmetric(vertical: size.height * 0.018),
            side: BorderSide(color: Colors.grey.shade300),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(30),
            ),
          ),
        );
      },
    );
  }
}
