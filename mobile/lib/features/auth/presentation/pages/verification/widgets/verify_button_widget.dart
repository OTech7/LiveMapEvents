import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../../../core/constants/colors.dart';
import '../../../../../../core/strings/app_strings.dart';
import '../../../../../../core/widgets/custom_button.dart';
import '../../../bloc/auth_bloc.dart';

class VerifyButtonWidget extends StatelessWidget {
  final VoidCallback onPressed;

  const VerifyButtonWidget({super.key, required this.onPressed});

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;

    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        return CustomButton(
          text: AppStrings.verifyAndContinue,
          onPressed: onPressed,
          isLoading: state is AuthenticationLoadingState,
          icon: Icons.arrow_forward_rounded,
        );
      },
    );
  }
}
