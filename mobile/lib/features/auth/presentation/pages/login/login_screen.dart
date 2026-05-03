import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../core/constants/colors.dart';
import '../../../../../core/data/countries.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../../../../core/widgets/country_code_picker.dart';
import '../../bloc/auth_bloc.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _phoneController = TextEditingController();

  // Defaults to Syria (+963). User can change via the picker.
  Country _country = Countries.defaultCountry;

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double hPad = size.width * 0.06; // ~6% of screen width
    final double iconSize = (size.width * 0.20).clamp(64.0, 100.0);
    final double vSpaceLg = size.height * 0.04;
    final double vSpaceMd = size.height * 0.025;
    final double vSpaceSm = size.height * 0.015;

    return Scaffold(
      backgroundColor: AppColors.kBackgroundColor,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        title: Text(
          AppStrings.login,
          style: Theme.of(context).textTheme.titleLarge,
        ),
        centerTitle: true,
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: EdgeInsets.symmetric(horizontal: hPad),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              SizedBox(height: vSpaceLg),
              Center(
                child: Container(
                  width: iconSize,
                  height: iconSize,
                  decoration: BoxDecoration(
                    color: AppColors.kSelectedColor,
                    shape: BoxShape.circle,
                  ),
                  child: Center(
                    child: Icon(
                      Icons.map,
                      size: iconSize * 0.5,
                      color: AppColors.kPrimaryColor,
                    ),
                  ),
                ),
              ),
              SizedBox(height: vSpaceLg),
              Text(
                AppStrings.welcomeBack,
                style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                  color: AppColors.kTextPrimaryColor,
                  fontWeight: FontWeight.bold,
                ),
                textAlign: TextAlign.center,
              ),
              SizedBox(height: vSpaceSm),
              Text(
                AppStrings.phonePrompt,
                style: Theme.of(context).textTheme.bodyMedium,
                textAlign: TextAlign.center,
              ),
              SizedBox(height: vSpaceLg),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  CountryCodePicker(
                    selected: _country,
                    onChanged: (c) => setState(() => _country = c),
                  ),
                  SizedBox(width: size.width * 0.03),
                  Expanded(
                    child: TextFormField(
                      controller: _phoneController,
                      keyboardType: TextInputType.phone,
                      inputFormatters: [
                        FilteringTextInputFormatter.digitsOnly,
                        // E.164 caps at 15 digits (excluding the country code).
                        LengthLimitingTextInputFormatter(15),
                      ],
                      decoration: InputDecoration(
                        filled: true,
                        fillColor: AppColors.kBackgroundColor,
                        hintText: AppStrings.enterMobileNumber,
                        hintStyle: TextStyle(color: Colors.grey.shade400),
                        enabledBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(15),
                          borderSide: BorderSide(
                            color: Colors.grey.shade300,
                          ),
                        ),
                        focusedBorder: OutlineInputBorder(
                          borderRadius: BorderRadius.circular(15),
                          borderSide: const BorderSide(
                            color: AppColors.kPrimaryColor,
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
              SizedBox(height: vSpaceMd),
              ElevatedButton(
                onPressed: () {
                  context.push('/verification_screen');
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.kPrimaryColor,
                  foregroundColor: Colors.white,
                  padding: EdgeInsets.symmetric(vertical: size.height * 0.02),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(30),
                  ),
                ),
                child: Text(
                  AppStrings.continueText,
                  style: Theme.of(context).textTheme.labelLarge,
                ),
              ),
              SizedBox(height: vSpaceMd),
              Row(
                children: [
                  Expanded(child: Divider(color: Colors.grey.shade300)),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Text(
                      AppStrings.or,
                      style: Theme.of(context).textTheme.bodySmall,
                    ),
                  ),
                  Expanded(child: Divider(color: Colors.grey.shade300)),
                ],
              ),
              SizedBox(height: vSpaceMd),
              OutlinedButton.icon(
                onPressed: () {
                  context.read<AuthBloc>().add(SignInWithGoogleEvent());
                },
                icon: const Icon(
                  Icons.g_mobiledata,
                  color: Colors.red,
                  size: 30,
                ),
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
              ),
              SizedBox(height: vSpaceLg),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    AppStrings.newToEventMap,
                    style: Theme.of(context).textTheme.bodyMedium,
                  ),
                  GestureDetector(
                    onTap: () {
                      context.push('/register');
                    },
                      child: Text(
                        AppStrings.createAnAccountText,
                        style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                              color: AppColors.kPrimaryColor,
                              fontWeight: FontWeight.bold,
                            ),
                      ),
                  ),
                ],
              ),
              SizedBox(height: vSpaceMd),
            ],
          ),
        ),
      ),
    );
  }
}
