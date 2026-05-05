import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

import '../../../../../../core/constants/colors.dart';
import '../../../../../../core/strings/app_strings.dart';
import '../../../bloc/auth_bloc.dart';

class LoginButtonWidget extends StatelessWidget {
  const LoginButtonWidget({
    super.key,
    required GlobalKey<FormState> formKey,
    required TextEditingController codeController,
    required TextEditingController phoneController,
    required this.size,
  }) : _formKey = formKey,
       _codeController = codeController,
       _phoneController = phoneController;

  final GlobalKey<FormState> _formKey;
  final TextEditingController _codeController;
  final TextEditingController _phoneController;
  final Size size;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<AuthBloc, AuthState>(
      builder: (context, state) {
        final isLoading = state is AuthenticationLoadingState;
        return ElevatedButton(
          onPressed: isLoading
              ? null
              : () {
                  if (_formKey.currentState!.validate()) {
                    final fullPhoneNumber =
                        "${_codeController.text}${_phoneController.text}";
                    context.read<AuthBloc>().add(SendOTPEvent(fullPhoneNumber));
                  }
                },
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.kPrimaryColor,
            foregroundColor: Colors.white,
            padding: EdgeInsets.symmetric(vertical: size.height * 0.02),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(30),
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
              : Text(
                  AppStrings.continueText,
                  style: Theme.of(context).textTheme.labelLarge,
                ),
        );
      },
    );
  }
}
