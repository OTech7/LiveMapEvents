import 'package:flutter/material.dart';
import '../../../../../../../core/constants/colors.dart';
import '../../../../../../../core/strings/app_strings.dart';

class StepIndicatorWidget extends StatelessWidget {
  final int currentStep;
  final int totalSteps;

  const StepIndicatorWidget({
    super.key,
    required this.currentStep,
    required this.totalSteps,
  });

  @override
  Widget build(BuildContext context) {
    final double vSpaceSm = MediaQuery.of(context).size.height * 0.01;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: List.generate(totalSteps, (index) {
            final isCompleted = index < currentStep;
            final isLast = index == totalSteps - 1;

            return Expanded(
              child: Container(
                margin: EdgeInsets.only(right: isLast ? 0 : 8),
                height: 6,
                decoration: BoxDecoration(
                  color: isCompleted
                      ? AppColors.kPrimaryColor
                      : AppColors.kSelectedColor,
                  borderRadius: BorderRadius.circular(4),
                ),
              ),
            );
          }),
        ),
        SizedBox(height: vSpaceSm),
        Text(
          AppStrings.step1of2,
          style: Theme.of(context).textTheme.labelSmall,
        ),
      ],
    );
  }
}
