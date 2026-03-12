import 'package:flutter/cupertino.dart';
import 'package:mobile/core/helper/regex.dart';
import '../strings/app_strings.dart';

class AppValidator {
  static FormFieldValidator<String> regularFieldValidator = (val) {
    if (val == null || val.isEmpty) {
      return AppStrings.thisFieldIsRequired;
    }
    return null;
  };

  static FormFieldValidator<String> passwordFieldValidator = (val) {
    if (val == null || val.length < 5) {
      return AppStrings.invalidPassword;
    }
    return null;
  };
  static FormFieldValidator<String> emailFieldValidator = (val) {
    if (val == null || val.isEmpty) {
      return AppStrings.requiredField;
    } else if (!RegexPatterns.email.hasMatch(val)) {
      return AppStrings.invalidEmail;
    }
    return null;
  };
  static FormFieldValidator genericFieldValidator = <T>(T val) {
    if (val == null) {
      return AppStrings.requiredField;
    }
    return null;
  };
  static FormFieldValidator<String> passportNumberValidator = (val) {
    if (!RegexPatterns.passportNumber.hasMatch(val!)) {
      return AppStrings.invalidPassport;
    }
    return null;
  };
  static FormFieldValidator<String> phoneNumberValidator = (val) {
    if (val == null || !RegexPatterns.phoneNumber.hasMatch(val)) {
      return AppStrings.invalidPhoneNumber;
    }
    return null;
  };
  static FormFieldValidator<String> dateValidator = (val) {
    if (val == null || val.isEmpty) {
      return AppStrings.requiredDate;
    }

    try {
      final parts = val.split('-');
      if (parts.length != 3) return AppStrings.invalidDate;

      final y = int.parse(parts[0]);
      final m = int.parse(parts[1]);
      final d = int.parse(parts[2]);

      final date = DateTime(y, m, d);
      return (date.year == y && date.month == m && date.day == d)
          ? null
          : AppStrings.invalidDate;
    } catch (_) {
      return AppStrings.invalidDate;
    }
  };
}
