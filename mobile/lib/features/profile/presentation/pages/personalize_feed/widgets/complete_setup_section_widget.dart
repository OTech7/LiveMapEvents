import 'package:flutter/material.dart';
import '../../../../../../../core/constants/colors.dart';
import '../../../../../../../core/strings/app_strings.dart';

class CompleteSetupSectionWidget extends StatelessWidget {
  final int selectedCount;
  final VoidCallback? onComplete;

  const CompleteSetupSectionWidget({
    super.key,
    required this.selectedCount,
    this.onComplete,
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
          ElevatedButton(
            onPressed: onComplete,
            style: ElevatedButton.styleFrom(
              backgroundColor: AppColors.kPrimaryColor,
              foregroundColor: Colors.white,
              disabledBackgroundColor: Colors.grey.shade300,
              padding: EdgeInsets.symmetric(vertical: size.height * 0.022),
              elevation: 4,
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Text(
                  "Complete Setup",
                  style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(width: 8),
                Icon(
                  selectedCount >= 3
                      ? Icons.check_circle_rounded
                      : Icons.check_circle_outline_rounded,
                  size: 20,
                ),
              ],
            ),
          ),
          SizedBox(height: vSpaceSm),
          Text(
            AppStrings.selectedCount.replaceFirst("{}", selectedCount.toString()),
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: selectedCount >= 3
                      ? AppColors.kPrimaryColor
                      : AppColors.kTextSecondaryColor,
                  fontWeight: selectedCount >= 3 ? FontWeight.bold : FontWeight.normal,
                ),
          ),
        ],
      ),
    );
  }
}
