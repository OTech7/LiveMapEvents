import 'package:flutter/material.dart';
import 'package:timer_count_down/timer_count_down.dart';
import '../../../../../../core/constants/colors.dart';
import '../../../../../../core/strings/app_strings.dart';

class ResendSectionWidget extends StatelessWidget {
  final bool canResend;
  final VoidCallback onResend;
  final VoidCallback onCountdownFinished;

  const ResendSectionWidget({
    super.key,
    required this.canResend,
    required this.onResend,
    required this.onCountdownFinished,
  });

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double vSpaceMd = size.height * 0.025;
    final double vSpaceSm = size.height * 0.015;

    return Container(
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
            style: Theme.of(
              context,
            ).textTheme.bodyMedium?.copyWith(fontSize: 15),
            textAlign: TextAlign.center,
          ),
          SizedBox(height: vSpaceSm),
          if (!canResend)
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
                    style: Theme.of(
                      context,
                    ).textTheme.titleSmall?.copyWith(letterSpacing: 2.0),
                  ),
                  interval: const Duration(seconds: 1),
                  onFinished: onCountdownFinished,
                ),
              ],
            )
          else
            GestureDetector(
              onTap: onResend,
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
                    style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      color: AppColors.kPrimaryColor,
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }
}
