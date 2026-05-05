import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../../../core/constants/colors.dart';
import '../../../../../../core/strings/app_strings.dart';
import '../../../bloc/auth_bloc.dart';

class VerifyButtonWidget extends StatelessWidget {
  final VoidCallback onPressed;

  const VerifyButtonWidget({super.key, required this.onPressed});

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;

    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        final isLoading = state is AuthenticationLoadingState;
        return ElevatedButton(
          onPressed: isLoading ? null : onPressed,
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.kPrimaryColor,
            foregroundColor: Colors.white,
            padding: EdgeInsets.symmetric(vertical: size.height * 0.022),
            elevation: 8,
            shadowColor: AppColors.kPrimaryColor.withOpacity(0.5),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(16),
            ),
          ),
          child: isLoading
              ? const SizedBox(
                  height: 24,
                  width: 24,
                  child: CircularProgressIndicator(
                    color: Colors.white,
                    strokeWidth: 2.5,
                  ),
                )
              : Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      AppStrings.verifyAndContinue,
                      style: Theme.of(context).textTheme.labelLarge?.copyWith(
                        fontSize: 18,
                        letterSpacing: 0.5,
                      ),
                    ),
                    const SizedBox(width: 8),
                    const Icon(Icons.arrow_forward_rounded, size: 20),
                  ],
                ),
        );
      },
    );
  }
}
