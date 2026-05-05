import 'package:flutter/material.dart';
import '../../../../../../core/constants/colors.dart';
import '../../../../../../core/strings/app_strings.dart';

class VerificationHeaderWidget extends StatelessWidget {
  const VerificationHeaderWidget({super.key});

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double iconOuter = (size.width * 0.22).clamp(70.0, 110.0);
    final double iconMid = (size.width * 0.16).clamp(52.0, 82.0);
    final double iconInner = (size.width * 0.12).clamp(38.0, 62.0);
    final double iconSz = (size.width * 0.1).clamp(32.0, 48.0);
    final double vSpaceLg = size.height * 0.04;
    final double vSpaceMd = size.height * 0.025;

    return Column(
      children: [
        SizedBox(height: vSpaceMd),
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
      ],
    );
  }
}
