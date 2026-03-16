import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../core/constants/colors.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../bloc/auth_bloc.dart';
import 'package:timer_count_down/timer_count_down.dart';

class VerificationScreen extends StatefulWidget {
  const VerificationScreen({super.key});

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

    // Add listeners to focus nodes to update UI on focus change
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

    // Auto submit if last field is filled
    if (value.length == 1 && index == 5) {
      _submit();
    }
  }

  void _submit() {
    FocusScope.of(context).unfocus();
    final code = _controllers.map((c) => c.text).join();
    if (code.length == 6) {
      context.read<AuthBloc>().add(VerifyEvent(code));
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

    // Responsive OTP box: fill width minus padding minus gaps between 6 boxes
    final double otpBoxWidth = ((size.width - hPad * 2 - 5 * 8) / 6).clamp(
      40.0,
      58.0,
    );
    final double otpBoxHeight = (otpBoxWidth * 1.25).clamp(50.0, 72.0);
    final double otpFontSize = (otpBoxWidth * 0.44).clamp(18.0, 26.0);

    // Icon ring sizes scaled to screen
    final double iconOuter = (size.width * 0.22).clamp(70.0, 110.0);
    final double iconMid = (size.width * 0.16).clamp(52.0, 82.0);
    final double iconInner = (size.width * 0.12).clamp(38.0, 62.0);
    final double iconSz = (size.width * 0.1).clamp(32.0, 48.0);

    final double vSpaceLg = size.height * 0.04;
    final double vSpaceMd = size.height * 0.025;
    final double vSpaceSm = size.height * 0.015;

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        automaticallyImplyLeading: false,
        title: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
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
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: AppColors.kPrimaryColor.withOpacity(0.08),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                AppStrings.step2of3,
                style: TextStyle(
                  color: AppColors.kPrimaryColor,
                  fontWeight: FontWeight.bold,
                  fontSize: 14,
                ),
              ),
            ),
          ],
        ),
      ),
      body: SafeArea(
        child: FadeTransition(
          opacity: _fadeAnimation,
          child: SingleChildScrollView(
            physics: const BouncingScrollPhysics(),
            padding: EdgeInsets.symmetric(horizontal: hPad, vertical: vSpaceSm),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                SizedBox(height: vSpaceMd),
                // Premium Icon presentation — sizes scale with screen width
                Center(
                  child: Container(
                    padding: EdgeInsets.all(iconOuter * 0.20),
                    decoration: BoxDecoration(
                      color: AppColors.kPrimaryColor.withOpacity(0.04),
                      shape: BoxShape.circle,
                    ),
                    child: Container(
                      padding: EdgeInsets.all(iconMid * 0.22),
                      decoration: BoxDecoration(
                        color: AppColors.kPrimaryColor.withOpacity(0.12),
                        shape: BoxShape.circle,
                      ),
                      child: Container(
                        padding: EdgeInsets.all(iconInner * 0.28),
                        decoration: BoxDecoration(
                          color: AppColors.kPrimaryColor,
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: AppColors.kPrimaryColor.withOpacity(0.4),
                              spreadRadius: 4,
                              blurRadius: 16,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Icon(
                          Icons.mark_email_read_rounded,
                          size: iconSz,
                          color: Colors.white,
                        ),
                      ),
                    ),
                  ),
                ),
                SizedBox(height: vSpaceLg),
                Text(
                  AppStrings.phoneVerification,
                  style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                    color: AppColors.kTextPrimaryColor,
                    fontWeight: FontWeight.w900,
                    letterSpacing: -0.5,
                  ),
                  textAlign: TextAlign.center,
                ),
                SizedBox(height: vSpaceMd),
                Container(
                  padding: EdgeInsets.symmetric(
                    horizontal: size.width * 0.05,
                    vertical: vSpaceMd,
                  ),
                  decoration: BoxDecoration(
                    color: AppColors.kBackgroundColor,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: Colors.grey.withOpacity(0.1)),
                  ),
                  child: Column(
                    children: [
                      Text(
                        AppStrings.enterOtpSent,
                        style: TextStyle(
                          color: AppColors.kTextSecondaryColor,
                          fontSize: 15,
                          height: 1.5,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: vSpaceSm),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Text(
                            "+963 ••• ••• •••",
                            style: TextStyle(
                              color: AppColors.kTextPrimaryColor,
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              letterSpacing: 1.2,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Icon(
                            Icons.edit_rounded,
                            size: 16,
                            color: AppColors.kPrimaryColor.withOpacity(0.7),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                SizedBox(height: vSpaceLg),
                // OTP boxes — width/height adapt to screen size
                Directionality(
                  textDirection: TextDirection.ltr,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: List.generate(6, (index) {
                      final isFocused = _focusNodes[index].hasFocus;
                      final isFilled = _controllers[index].text.isNotEmpty;

                      return AnimatedContainer(
                        duration: const Duration(milliseconds: 200),
                        width: otpBoxWidth,
                        height: otpBoxHeight,
                        decoration: BoxDecoration(
                          color:
                              // isFocused
                              //     ? Colors.white
                              //     :
                              AppColors.kBackgroundColor,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(
                            color: isFocused
                                ? AppColors.kPrimaryColor
                                : isFilled
                                ? AppColors.kPrimaryColor.withOpacity(0.3)
                                : Colors.transparent,
                            width: 2,
                          ),
                          boxShadow: isFocused
                              ? [
                                  BoxShadow(
                                    color: AppColors.kPrimaryColor.withOpacity(
                                      0.15,
                                    ),
                                    blurRadius: 10,
                                    spreadRadius: 0,
                                    offset: const Offset(0, 4),
                                  ),
                                ]
                              : [],
                        ),
                        child: Center(
                          child: TextFormField(
                            controller: _controllers[index],
                            focusNode: _focusNodes[index],
                            keyboardType: TextInputType.number,
                            textAlign: TextAlign.center,
                            maxLength: 1,
                            style: TextStyle(
                              fontSize: otpFontSize,
                              fontWeight: FontWeight.bold,
                              color: isFocused
                                  ? AppColors.kPrimaryColor
                                  : AppColors.kTextPrimaryColor,
                            ),
                            cursorColor: AppColors.kPrimaryColor,
                            decoration: const InputDecoration(
                              fillColor: Colors.transparent,
                              filled: true,
                              counterText: "",
                              border: InputBorder.none,
                              enabledBorder: InputBorder.none,
                              focusedBorder: InputBorder.none,
                              errorBorder: InputBorder.none,
                              disabledBorder: InputBorder.none,
                              isDense: true,
                              contentPadding: EdgeInsets.zero,
                            ),
                            onChanged: (value) => _onInputChanged(value, index),
                          ),
                        ),
                      );
                    }),
                  ),
                ),
                SizedBox(height: vSpaceLg),
                // Resend section
                Container(
                  padding: EdgeInsets.symmetric(
                    vertical: vSpaceMd,
                    horizontal: size.width * 0.05,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(color: Colors.grey.withOpacity(0.1)),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.grey.withOpacity(0.04),
                        spreadRadius: 1,
                        blurRadius: 10,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: Column(
                    children: [
                      Text(
                        AppStrings.didntReceiveCode,
                        style: TextStyle(
                          color: AppColors.kTextSecondaryColor,
                          fontSize: 15,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: vSpaceSm),
                      if (!_canResend)
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(
                              Icons.timer_outlined,
                              size: 18,
                              color: AppColors.kLightGreyColor,
                            ),
                            const SizedBox(width: 8),
                            Countdown(
                              seconds: 59,
                              build: (BuildContext context, double time) => Text(
                                "00:${time.toInt().toString().padLeft(2, '0')}",
                                style: const TextStyle(
                                  color: AppColors.kTextPrimaryColor,
                                  fontSize: 16,
                                  fontWeight: FontWeight.bold,
                                  letterSpacing: 2.0,
                                ),
                              ),
                              interval: const Duration(seconds: 1),
                              onFinished: () {
                                if (mounted) {
                                  setState(() {
                                    _canResend = true;
                                  });
                                }
                              },
                            ),
                          ],
                        )
                      else
                        GestureDetector(
                          onTap: () {
                            setState(() {
                              _canResend = false;
                            });
                            ScaffoldMessenger.of(context).showSnackBar(
                              SnackBar(
                                content: Text(AppStrings.codeSentSuccess),
                              ),
                            );
                          },
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              const Icon(
                                Icons.refresh_rounded,
                                color: AppColors.kPrimaryColor,
                                size: 20,
                              ),
                              const SizedBox(width: 8),
                              Text(
                                AppStrings.resend,
                                style: const TextStyle(
                                  color: AppColors.kPrimaryColor,
                                  fontWeight: FontWeight.bold,
                                  fontSize: 16,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
                SizedBox(height: vSpaceLg),
                ElevatedButton(
                  onPressed: _submit,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.kPrimaryColor,
                    foregroundColor: Colors.white,
                    padding: EdgeInsets.symmetric(
                      vertical: size.height * 0.022,
                    ),
                    elevation: 8,
                    shadowColor: AppColors.kPrimaryColor.withOpacity(0.5),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(16),
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        AppStrings.verifyAndContinue,
                        style: const TextStyle(
                          fontSize: 18,
                          fontWeight: FontWeight.bold,
                          letterSpacing: 0.5,
                        ),
                      ),
                      const SizedBox(width: 8),
                      const Icon(Icons.arrow_forward_rounded, size: 20),
                    ],
                  ),
                ),
                SizedBox(height: vSpaceMd),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
