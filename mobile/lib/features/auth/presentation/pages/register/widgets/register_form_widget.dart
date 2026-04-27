import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../../../core/helper/validator.dart';
import '../../../../../../core/strings/app_strings.dart';
import '../../widgets/auth_fields.dart';

class RegisterFormWidget extends StatelessWidget {
  const RegisterFormWidget({
    super.key,
    required this.vSpaceLg,
    required TextEditingController firstNameController,
    required this.vSpaceSm,
    required TextEditingController lastNameController,
    required TextEditingController emailController,
    required TextEditingController phoneController,
    required TextEditingController passwordController,
    required bool obscurePassword,
    required TextEditingController confirmPasswordController,
    required bool obscureConfirmPassword,
  }) : _firstNameController = firstNameController,
       _lastNameController = lastNameController,
       _emailController = emailController,
       _phoneController = phoneController,
       _passwordController = passwordController,
       _obscurePassword = obscurePassword,
       _confirmPasswordController = confirmPasswordController,
       _obscureConfirmPassword = obscureConfirmPassword;

  final double vSpaceLg;
  final TextEditingController _firstNameController;
  final double vSpaceSm;
  final TextEditingController _lastNameController;
  final TextEditingController _emailController;
  final TextEditingController _phoneController;
  final TextEditingController _passwordController;
  final bool _obscurePassword;
  final TextEditingController _confirmPasswordController;
  final bool _obscureConfirmPassword;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        SizedBox(height: vSpaceLg),
        CustomTextFieldWidget(
          validator: AppValidator.regularFieldValidator,
          controller: _firstNameController,
          hintText: AppStrings.firstName,
          icon: Icons.person_outline,
        ),
        SizedBox(height: vSpaceSm),
        CustomTextFieldWidget(
          validator: AppValidator.regularFieldValidator,
          controller: _lastNameController,
          hintText: AppStrings.lastName,
          icon: Icons.person_outline,
        ),
        SizedBox(height: vSpaceSm),
        CustomTextFieldWidget(
          validator: AppValidator.emailFieldValidator,
          controller: _emailController,
          hintText: AppStrings.email,
          icon: Icons.email_outlined,
          keyboardType: TextInputType.emailAddress,
        ),
        SizedBox(height: vSpaceSm),
        CustomTextFieldWidget(
          controller: _phoneController,
          icon: Icons.phone,
          formatters: [
            FilteringTextInputFormatter.digitsOnly,
            LengthLimitingTextInputFormatter(9),
          ],
          hintText: AppStrings.phoneNumber,
          keyboardType: TextInputType.phone,
          validator: AppValidator.phoneNumberValidator,
        ),
        SizedBox(height: vSpaceSm),
        PasswordInputField(
          controller: _passwordController,
          hintText: AppStrings.password,
          isObscured: _obscurePassword,
          validator: AppValidator.passwordFieldValidator,
        ),
        SizedBox(height: vSpaceSm),
        PasswordInputField(
          controller: _confirmPasswordController,
          hintText: AppStrings.confirmPassword,
          isObscured: _obscureConfirmPassword,
          validator: (val) {
            if (val != null &&
                val.length > 5 &&
                _passwordController.text == _confirmPasswordController.text) {
              return null;
            }
            return AppStrings.passwordsNotMatched;
          },
        ),
        SizedBox(height: vSpaceLg),
      ],
    );
  }
}
