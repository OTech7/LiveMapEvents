import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:mobile/features/auth/presentation/pages/login/widgets/new_user_widget.dart';
import '../../../../../core/constants/colors.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../bloc/auth_bloc.dart';
import 'widgets/divider_widget.dart';
import 'widgets/login_button_widget.dart';
import 'widgets/login_fields_widget.dart';
import 'widgets/login_logo_widget.dart';
import 'widgets/login_with_google_widget.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _codeController = TextEditingController(
    text: "+963",
  );
  final GlobalKey<FormState> _formKey = GlobalKey<FormState>();

  @override
  void dispose() {
    _phoneController.dispose();
    _codeController.dispose();
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
          child: BlocListener<AuthBloc, AuthState>(
            listener: (context, state) {
              if (state is OTPSentSuccessfullyState) {
                context.push(
                  '/verification_screen',
                  extra: {'phoneNumber': state.phoneNumber},
                );
              } else if (state is AuthenticatedState) {
                if (state.authEntity.profileComplete) {
                  context.go('/nav_screen');
                } else {
                  context.push('/set_up_profile');
                }
              }
            },
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  SizedBox(height: vSpaceLg),
                  LoginLogoWidget(iconSize: iconSize),
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
                  LoginFieldsWidget(
                    codeController: _codeController,
                    size: size,
                    phoneController: _phoneController,
                  ),
                  SizedBox(height: vSpaceMd),
                  LoginButtonWidget(
                    formKey: _formKey,
                    codeController: _codeController,
                    phoneController: _phoneController,
                    size: size,
                  ),
                  SizedBox(height: vSpaceMd),
                  DividerWidget(),
                  SizedBox(height: vSpaceMd),
                  LoginWithGoogleWidget(size: size),
                  SizedBox(height: vSpaceLg),
                  NewUserWidget(),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
