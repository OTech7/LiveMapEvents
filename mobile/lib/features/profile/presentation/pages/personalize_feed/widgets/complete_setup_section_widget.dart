import 'package:flutter/material.dart';
import '../../../../../../../core/constants/colors.dart';
import '../../../../../../../core/strings/app_strings.dart';
import '../../../../../../core/widgets/custom_button.dart';

class CompleteSetupSectionWidget extends StatelessWidget {
  final int selectedCount;
  final VoidCallback? onComplete;
  final bool isLoading;

  const CompleteSetupSectionWidget({
    super.key,
    required this.selectedCount,
    this.onComplete,
    this.isLoading = false,
  });

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double hPad = size.width * 0.05;
    final double vSpaceSm = size.height * 0.01;

    return Container(
      padding: EdgeInsets.all(hPad),
      decoration: BoxDecoration(
        color: Colors.white,
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, -5),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          CustomButton(
            text: AppStrings.completeSetup,
            onPressed: onComplete,
            isLoading: isLoading,
            isDisabled: onComplete == null,
            icon: selectedCount >= 3
                ? Icons.check_circle_rounded
                : Icons.check_circle_outline_rounded,
          ),
          SizedBox(height: vSpaceSm),
          Text(
            AppStrings.selectedCount.replaceFirst(
              "{}",
              selectedCount.toString(),
            ),
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
              color: selectedCount >= 3
                  ? AppColors.kPrimaryColor
                  : AppColors.kTextSecondaryColor,
              fontWeight: selectedCount >= 3
                  ? FontWeight.bold
                  : FontWeight.normal,
            ),
          ),
        ],
      ),
    );
  }
}
