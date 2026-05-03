import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../../../core/constants/colors.dart';

class PasswordInputField extends StatelessWidget {
  final TextEditingController controller;
  bool isObscured;
  final String hintText;
  FormFieldValidator<String>? validator;

  PasswordInputField({
    super.key,
    required this.controller,
    required this.isObscured,
    required this.validator,
    required this.hintText,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(10),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: StatefulBuilder(
        builder: (context, setState) {
          return TextFormField(
            validator: validator,
            controller: controller,
            obscureText: isObscured,
            decoration: InputDecoration(
              fillColor: Colors.white,
              suffixIcon: IconButton(
                onPressed: () {
                  setState(() {
                    isObscured = !isObscured;
                  });
                },
                icon: Icon(
                  !isObscured ? Icons.visibility : Icons.visibility_off,
                  color: AppColors.kLightGreyColor,
                ),
              ),
              prefixIcon: const Icon(
                Icons.password_outlined,
                color: AppColors.kLightGreyColor,
              ),
              hintText: hintText,
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 15,
                vertical: 15,
              ),
            ),
          );
        },
      ),
    );
  }
}

class CustomTextFieldWidget extends StatelessWidget {
  final TextEditingController controller;
  final String hintText;
  final String? prefixText;
  final IconData icon;
  final TextInputType? keyboardType;
  FormFieldValidator<String>? validator;
  List<TextInputFormatter>? formatters;

  CustomTextFieldWidget({
    super.key,
    required this.controller,
    required this.hintText,
    this.prefixText,
    this.keyboardType,
    this.validator,
    this.formatters,
    required this.icon,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(10),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: TextFormField(
        inputFormatters: formatters,
        validator: validator,
        controller: controller,
        keyboardType: keyboardType,
        decoration: InputDecoration(
          prefixText: prefixText,
          fillColor: Colors.white,
          hintText: hintText,
          border: InputBorder.none,
          contentPadding: const EdgeInsets.symmetric(
            horizontal: 15,
            vertical: 15,
          ),
          prefixIcon: Icon(icon, color: AppColors.kLightGreyColor),
        ),
      ),
    );
  }
}
