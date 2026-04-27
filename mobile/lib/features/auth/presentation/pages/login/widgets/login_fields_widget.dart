import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../../../core/constants/colors.dart';
import '../../../../../../core/helper/validator.dart';
import '../../../../../../core/strings/app_strings.dart';

class LoginFieldsWidget extends StatelessWidget {
  const LoginFieldsWidget({
    super.key,
    required TextEditingController codeController,
    required this.size,
    required TextEditingController phoneController,
  }) : _codeController = codeController,
       _phoneController = phoneController;

  final TextEditingController _codeController;
  final Size size;
  final TextEditingController _phoneController;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          flex: 2,
          child: TextFormField(
            controller: _codeController,
            readOnly: true,
            keyboardType: TextInputType.phone,
            decoration: InputDecoration(
              filled: true,
              fillColor: AppColors.kBackgroundColor,
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(15),
                borderSide: BorderSide(color: Colors.grey.shade300),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(15),
                borderSide: const BorderSide(color: AppColors.kPrimaryColor),
              ),
            ),
          ),
        ),
        SizedBox(width: size.width * 0.03),
        Expanded(
          flex: 5,
          child: TextFormField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            inputFormatters: [
              FilteringTextInputFormatter.digitsOnly,
              LengthLimitingTextInputFormatter(9),
            ],
            decoration: InputDecoration(
              filled: true,
              fillColor: AppColors.kBackgroundColor,
              hintText: AppStrings.enterMobileNumber,
              hintStyle: TextStyle(color: Colors.grey.shade400),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(15),
                borderSide: BorderSide(color: Colors.grey.shade300),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(15),
                borderSide: const BorderSide(color: AppColors.kPrimaryColor),
              ),
            ),
            validator: AppValidator.phoneNumberValidator,
          ),
        ),
      ],
    );
  }
}
