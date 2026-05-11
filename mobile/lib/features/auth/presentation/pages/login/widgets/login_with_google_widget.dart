import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../../../core/strings/app_strings.dart';
import '../../../bloc/auth_bloc.dart';

class LoginWithGoogleWidget extends StatelessWidget {
  const LoginWithGoogleWidget({super.key, required this.size});

  final Size size;

  @override
  Widget build(BuildContext context) {
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
