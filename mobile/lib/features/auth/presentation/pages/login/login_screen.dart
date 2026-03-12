import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../../../core/constants/colors.dart';
import '../../../../../core/helper/validator.dart';
import '../../../../../core/strings/app_strings.dart';
import '../widgets/auth_fields.dart';
import 'widgets/go_register_button.dart';
import 'widgets/login_button.dart';
import 'widgets/login_header_widget.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _countryCodeController =
      TextEditingController(text: "963");
  final TextEditingController _emailController = TextEditingController();
  final bool _obscurePassword = true;
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    _countryCodeController.dispose();
    _emailController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.kBackgroundColor,
      body: SafeArea(
        child: LayoutBuilder(
          builder: (context, constraints) {
            return SingleChildScrollView(
              child: ConstrainedBox(
                constraints: BoxConstraints(minHeight: constraints.maxHeight),
                child: Form(
                  key: _formKey,
                  child: IntrinsicHeight(
                    child: Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 24.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          SizedBox(height: constraints.maxHeight * 0.05),
                          const LoginHeaderWidget(),
                          SizedBox(height: constraints.maxHeight * 0.05),
                          Text(
                            AppStrings.welcomeBack,
                            style: Theme.of(context)
                                .textTheme
                                .titleLarge!
                                .copyWith(fontSize: 28),
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            AppStrings.login,
                            style: Theme.of(context).textTheme.titleSmall,
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 40),
                          CustomTextFieldWidget(
                            controller: _emailController,
                            keyboardType: TextInputType.emailAddress,
                            hintText: AppStrings.email,
                            icon: Icons.email,
                            validator: AppValidator.emailFieldValidator,
                          ),
                          const SizedBox(height: 20),
                          PasswordInputField(
                            controller: _passwordController,
                            isObscured: _obscurePassword,
                            validator: AppValidator.passwordFieldValidator,
                            hintText: AppStrings.password,
                          ),
                          const SizedBox(height: 12),
                          Align(
                            alignment: Alignment.centerRight,
                            child: TextButton(
                              onPressed: () {
                                // TODO: Implement forgot password
                              },
                              child: Text(AppStrings.forgotPassword),
                            ),
                          ),
                          const SizedBox(height: 40),
                          buildLoginButton(
                              emailController: _emailController,
                              passwordController: _passwordController,
                              formKey: _formKey),
                          const SizedBox(height: 20),
                          const GoRegisterButton(),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                AppStrings.signInToContinue,
                                style: Theme.of(context).textTheme.bodyMedium,
                                textAlign: TextAlign.center,
                              ),
                              TextButton(
                                onPressed: () {
                                  context.go("/nav_screen");
                                },
                                child: Text(
                                  "AppStrings.guest",
                                  style: Theme.of(context).textTheme.bodyLarge,
                                  textAlign: TextAlign.center,
                                ),
                              ),
                            ],
                          ),
                          SizedBox(height: constraints.maxHeight * 0.03),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}
