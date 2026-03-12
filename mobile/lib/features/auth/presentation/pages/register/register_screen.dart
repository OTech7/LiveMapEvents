import 'package:flutter/material.dart';

import '../../../../../core/constants/colors.dart';
import '../../../../../core/helper/validator.dart';
import '../../../../../core/strings/app_strings.dart';
import '../login/widgets/login_header_widget.dart';
import '../widgets/auth_fields.dart';
import 'widgets/go_login_button.dart';
import 'widgets/register_button.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final TextEditingController _firstNameController = TextEditingController();
  final TextEditingController _lastNameController = TextEditingController();
  final TextEditingController _emailController = TextEditingController();

  // final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _confirmPasswordController =
      TextEditingController();

  // final TextEditingController _countryCodeController = TextEditingController();
  final bool _obscurePassword = true;
  final bool _obscureConfirmPassword = true;
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    // _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    // _countryCodeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.kBackgroundColor,
      body: SafeArea(
        child: SingleChildScrollView(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 24.0),
            child: Form(
              key: _formKey,
              autovalidateMode: AutovalidateMode.onUserInteraction,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: 15),
                  const LoginHeaderWidget(),
                  const SizedBox(height: 20),
                  Text(
                    AppStrings.createAccount,
                    style: Theme.of(context)
                        .textTheme
                        .titleLarge!
                        .copyWith(fontSize: 28, color: AppColors.kPrimaryColor),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 8),
                  Text(
                    AppStrings.signupToStart,
                    style: Theme.of(context).textTheme.titleSmall,
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 20),
                  CustomTextFieldWidget(
                    validator: AppValidator.regularFieldValidator,
                    controller: _firstNameController,
                    hintText: AppStrings.firstName,
                    icon: Icons.person_outline,
                  ),
                  const SizedBox(height: 12),
                  CustomTextFieldWidget(
                    validator: AppValidator.regularFieldValidator,
                    controller: _lastNameController,
                    hintText: AppStrings.lastName,
                    icon: Icons.person_outline,
                  ),
                  const SizedBox(height: 12),
                  CustomTextFieldWidget(
                    validator: AppValidator.emailFieldValidator,
                    controller: _emailController,
                    hintText: AppStrings.email,
                    icon: Icons.email_outlined,
                    keyboardType: TextInputType.emailAddress,
                  ),
                  const SizedBox(height: 12),
                  PasswordInputField(
                    controller: _passwordController,
                    hintText: AppStrings.password,
                    isObscured: _obscurePassword,
                    validator: AppValidator.passwordFieldValidator,
                  ),
                  const SizedBox(height: 12),
                  PasswordInputField(
                    controller: _confirmPasswordController,
                    hintText: AppStrings.confirmPassword,
                    isObscured: _obscureConfirmPassword,
                    validator: (val) {
                      if (val != null &&
                          val.length > 5 &&
                          _passwordController.text ==
                              _confirmPasswordController.text) {
                        return null;
                      }
                      return AppStrings.passwordsNotMatched;
                    },
                  ),
                  const SizedBox(height: 20),
                  buildRegisterButton(
                      firstNameController: _firstNameController,
                      lastNameController: _lastNameController,
                      emailController: _emailController,
                      formKey: _formKey,
                      passwordController: _passwordController),
                  const SizedBox(height: 10),
                  buildLoginLink(context),
                  const SizedBox(height: 20),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
