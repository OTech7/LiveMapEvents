import 'package:flutter/material.dart';
import '../../../../../../core/constants/colors.dart';

class OtpInputRowWidget extends StatelessWidget {
  final List<TextEditingController> controllers;
  final List<FocusNode> focusNodes;
  final Function(String, int) onInputChanged;

  const OtpInputRowWidget({
    super.key,
    required this.controllers,
    required this.focusNodes,
    required this.onInputChanged,
  });

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double hPad = size.width * 0.06;

    final double otpBoxWidth = ((size.width - hPad * 2 - 5 * 8) / 6).clamp(
      40.0,
      58.0,
    );
    final double otpBoxHeight = (otpBoxWidth * 1.25).clamp(50.0, 72.0);
    final double otpFontSize = (otpBoxWidth * 0.44).clamp(18.0, 26.0);

    return Directionality(
      textDirection: TextDirection.ltr,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: List.generate(6, (index) {
          final isFocused = focusNodes[index].hasFocus;
          final isFilled = controllers[index].text.isNotEmpty;

          return AnimatedContainer(
            duration: const Duration(milliseconds: 200),
            width: otpBoxWidth,
            height: otpBoxHeight,
            decoration: BoxDecoration(
              color: AppColors.kBackgroundColor,
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
                        color: AppColors.kPrimaryColor.withOpacity(0.15),
                        blurRadius: 10,
                        spreadRadius: 0,
                        offset: const Offset(0, 4),
                      ),
                    ]
                  : [],
            ),
            child: Center(
              child: TextFormField(
                controller: controllers[index],
                focusNode: focusNodes[index],
                keyboardType: TextInputType.number,
                textAlign: TextAlign.center,
                maxLength: 1,
                style: Theme.of(context).textTheme.titleSmall?.copyWith(
                      fontSize: otpFontSize,
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
                onChanged: (value) => onInputChanged(value, index),
              ),
            ),
          );
        }),
      ),
    );
  }
}
