import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../../../core/data/countries.dart';
import '../../../../../../core/helper/validator.dart';
import '../../../../../../core/strings/app_strings.dart';
import '../../../../../../core/widgets/country_code_picker.dart';
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
    required this.selectedCountry,
    required this.onCountryChanged,
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
  final Country selectedCountry;
  final ValueChanged<Country> onCountryChanged;
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
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CountryCodePicker(
              selected: selectedCountry,
              onChanged: onCountryChanged,
              style: CountryCodePickerStyle.field,
            ),
            const SizedBox(width: 8),
            Expanded(
              child: TextFormField(
                controller: _phoneController,
                keyboardType: TextInputType.phone,
                inputFormatters: [
                  FilteringTextInputFormatter.digitsOnly,
                  LengthLimitingTextInputFormatter(15),
                ],
                decoration: InputDecoration(
                  filled: true,
                  fillColor: Theme
                      .of(context)
                      .inputDecorationTheme
                      .fillColor,
                  hintText: AppStrings.phoneNumber,
                  hintStyle: TextStyle(color: Colors.grey.shade400),
                  prefixIcon: const Icon(Icons.phone),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(15),
                    borderSide: BorderSide(color: Colors.grey.shade300),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(15),
                    borderSide: BorderSide(
                      color: Theme
                          .of(context)
                          .colorScheme
                          .primary,
                    ),
                  ),
                  errorBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(15),
                    borderSide: const BorderSide(color: Colors.red),
                  ),
                  focusedErrorBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(15),
                    borderSide: const BorderSide(color: Colors.red),
                  ),
                ),
                validator: AppValidator.phoneNumberValidator,
              ),
            ),
          ],
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
