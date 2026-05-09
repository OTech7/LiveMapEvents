import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../core/constants/colors.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../bloc/auth_bloc.dart';
import 'widgets/otp_input_row_widget.dart';
import 'widgets/phone_number_display_widget.dart';
import 'widgets/resend_section_widget.dart';
import 'widgets/verification_header_widget.dart';
import 'widgets/verify_button_widget.dart';

class VerificationScreen extends StatefulWidget {
  final String phoneNumber;

  const VerificationScreen({super.key, required this.phoneNumber});

  @override
  State<VerificationScreen> createState() => _VerificationScreenState();
}

class _VerificationScreenState extends State<VerificationScreen>
    with SingleTickerProviderStateMixin {
  final List<TextEditingController> _controllers = List.generate(
    6,
    (index) => TextEditingController(),
  );
  final List<FocusNode> _focusNodes = List.generate(6, (index) => FocusNode());
  bool _canResend = false;
  late AnimationController _fadeController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _fadeController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _fadeAnimation = CurvedAnimation(
      parent: _fadeController,
      curve: Curves.easeOutCubic,
    );
    _fadeController.forward();

    for (var node in _focusNodes) {
      node.addListener(() {
        if (mounted) setState(() {});
      });
    }
  }

  @override
  void dispose() {
    _fadeController.dispose();
    for (var controller in _controllers) {
      controller.dispose();
    }
    for (var node in _focusNodes) {
      node.dispose();
    }
    super.dispose();
  }

  void _onInputChanged(String value, int index) {
    if (value.length == 1 && index < 5) {
      FocusScope.of(context).requestFocus(_focusNodes[index + 1]);
    } else if (value.isEmpty && index > 0) {
      FocusScope.of(context).requestFocus(_focusNodes[index - 1]);
    }

    if (value.length == 1 && index == 5) {
      _submit();
    }
  }

  void _submit() {
    FocusScope.of(context).unfocus();
    final code = _controllers.map((c) => c.text).join();
    if (code.length == 6) {
      context.read<AuthBloc>().add(
        VerifyEvent(code: code, phoneNumber: widget.phoneNumber),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(AppStrings.enterFullCode, textAlign: TextAlign.right),
          backgroundColor: AppColors.kRedColor,
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double hPad = size.width * 0.06;
    final double vSpaceLg = size.height * 0.04;
    final double vSpaceMd = size.height * 0.025;
    final double vSpaceSm = size.height * 0.015;

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        automaticallyImplyLeading: false,
        actions: [
          Container(
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white,
              border: Border.all(color: Colors.grey.shade200),
              boxShadow: [
                BoxShadow(
                  color: Colors.grey.withOpacity(0.05),
                  spreadRadius: 2,
                  blurRadius: 10,
                  offset: const Offset(0, 2),
                ),
              ],
            ),
            child: IconButton(
              icon: const Icon(
                Icons.arrow_forward_ios_rounded,
                color: AppColors.kTextPrimaryColor,
                size: 18,
              ),
              onPressed: () => context.pop(),
            ),
          ),
        ],
      ),
      body: BlocListener<AuthBloc, AuthState>(
        listener: (context, state) {
          if (state is AuthenticatedState) {
            if (state.authEntity.profileComplete) {
              context.go('/nav_screen');
            } else {
              context.push('/set_up_profile');
            }
          }
          if (state is AuthenticationErrorState) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(state.message),
                backgroundColor: AppColors.kRedColor,
              ),
            );
            // context.push('/set_up_profile');
          }
        },
        child: SafeArea(
          child: FadeTransition(
            opacity: _fadeAnimation,
            child: SingleChildScrollView(
              physics: const BouncingScrollPhysics(),
              padding: EdgeInsets.symmetric(
                horizontal: hPad,
                vertical: vSpaceSm,
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const VerificationHeaderWidget(),
                  SizedBox(height: vSpaceMd),
                  PhoneNumberDisplayWidget(phoneNumber: widget.phoneNumber),
                  SizedBox(height: vSpaceLg),
                  OtpInputRowWidget(
                    controllers: _controllers,
                    focusNodes: _focusNodes,
                    onInputChanged: _onInputChanged,
                  ),
                  SizedBox(height: vSpaceLg),
                  ResendSectionWidget(
                    canResend: _canResend,
                    onResend: () {
                      context.read<AuthBloc>().add(
                        SendOTPEvent(widget.phoneNumber),
                      );
                      setState(() {
                        _canResend = false;
                      });
                    },
                    onCountdownFinished: () {
                      if (mounted) {
                        setState(() {
                          _canResend = true;
                        });
                      }
                    },
                  ),
                  SizedBox(height: vSpaceLg),
                  VerifyButtonWidget(onPressed: _submit),
                  SizedBox(height: vSpaceMd),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
