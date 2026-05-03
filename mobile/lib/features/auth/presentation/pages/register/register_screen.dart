import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../../core/constants/colors.dart';
import '../../../../../core/data/countries.dart';
import '../../../../../core/helper/validator.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../../../../core/widgets/country_code_picker.dart';
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

  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _passwordController = TextEditingController();
  final TextEditingController _confirmPasswordController =
      TextEditingController();

  // Defaults to Syria (+963). User can change via the picker.
  Country _country = Countries.defaultCountry;

  // Mirror the dial code into a TextEditingController so that the existing
  // RegisterPayload contract (which expects a controller) keeps working.
  late final TextEditingController _countryCodeController =
  TextEditingController(text: _country.dialCode);
  final bool _obscurePassword = true;
  final bool _obscureConfirmPassword = true;
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    _countryCodeController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double hPad = size.width * 0.06;
    final double vPad = size.width * 0.1;
    final double vSpaceMd = size.height * 0.02;
    final double vSpaceSm = size.height * 0.012;
    final double vSpaceLg = size.height * 0.025;

    return Scaffold(
      backgroundColor: AppColors.kBackgroundColor,
      body: SafeArea(
        child: SingleChildScrollView(
          child: Padding(
            padding: EdgeInsets.symmetric(horizontal: hPad, vertical: vPad),
            child: Form(
              key: _formKey,
              autovalidateMode: AutovalidateMode.onUserInteraction,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  SizedBox(height: vSpaceLg),
                  Text(
                    AppStrings.createAccount,
                    style: Theme.of(context).textTheme.headlineLarge?.copyWith(
                          color: AppColors.kPrimaryColor,
                        ),
                    textAlign: TextAlign.center,
                  ),
                  SizedBox(height: vSpaceSm),
                  Text(
                    AppStrings.signupToStart,
                    style: Theme.of(context).textTheme.titleSmall,
                    textAlign: TextAlign.center,
                  ),
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
                        selected: _country,
                        onChanged: (c) =>
                            setState(() {
                              _country = c;
                              _countryCodeController.text = c.dialCode;
                            }),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: CustomTextFieldWidget(
                          controller: _phoneController,
                          icon: Icons.phone,
                          formatters: [
                            FilteringTextInputFormatter.digitsOnly,
                            // E.164 caps at 15 digits (excluding country code).
                            LengthLimitingTextInputFormatter(15),
                          ],
                          hintText: AppStrings.phoneNumber,
                          keyboardType: TextInputType.phone,
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
                          _passwordController.text ==
                              _confirmPasswordController.text) {
                        return null;
                      }
                      return AppStrings.passwordsNotMatched;
                    },
                  ),
                  SizedBox(height: vSpaceLg),
                  buildRegisterButton(
                    firstNameController: _firstNameController,
                    lastNameController: _lastNameController,
                    emailController: _emailController,
                    phoneController: _phoneController,
                    codeController: _countryCodeController,
                    formKey: _formKey,
                    passwordController: _passwordController,
                  ),
                  SizedBox(height: vSpaceSm),
                  buildLoginLink(context),
                  SizedBox(height: vSpaceLg),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
