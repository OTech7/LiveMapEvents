import 'package:flutter/material.dart';
import '../../../../../../core/constants/colors.dart';
import '../../../../../../core/strings/app_strings.dart';

class PhoneNumberDisplayWidget extends StatelessWidget {
  final String phoneNumber;
  const PhoneNumberDisplayWidget({super.key, required this.phoneNumber});

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double vSpaceMd = size.height * 0.025;
    final double vSpaceSm = size.height * 0.015;

    return Container(
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
            style: Theme.of(context)
                .textTheme
                .bodyMedium
                ?.copyWith(fontSize: 15, height: 1.5),
            textAlign: TextAlign.center,
          ),
          SizedBox(height: vSpaceSm),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                phoneNumber,
                style: Theme.of(context)
                    .textTheme
                    .titleSmall
                    ?.copyWith(letterSpacing: 1.2),
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
    );
  }
}
