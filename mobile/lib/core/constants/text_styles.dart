import 'package:flutter/material.dart';

import 'colors.dart';

class TextStyles {
  static const TextStyle titleLarge = TextStyle(
    fontSize: 20,
    // fontFamily: AppFonts.kCairoBoldFont,
    color: AppColors.kTextPrimaryColor,
  );

  static const TextStyle titleMedium = TextStyle(
    fontSize: 18,
    // fontFamily: AppFonts.kCairoBoldFont,
    color: AppColors.kTextPrimaryColor,
  );

  static const TextStyle titleSmall = TextStyle(
    fontSize: 16,
    // fontFamily: AppFonts.kCairoBoldFont,
    color: AppColors.kTextPrimaryColor,
  );

  // --- Body Styles (Normal/Medium Weight)
  static const TextStyle bodyLarge = TextStyle(
    fontSize: 16,
    // fontFamily: AppFonts.kCairoNormalFont,
    fontWeight: FontWeight.w500,
    color: AppColors.kTextPrimaryColor,
  );

  static const TextStyle bodyMedium = TextStyle(
    fontSize: 13,
    // fontFamily: AppFonts.kCairoNormalFont,
    color: AppColors.kTextSecondaryColor,
  );
  static const TextStyle headlineMedium = TextStyle(
    fontSize: 13,
    // fontFamily: AppFonts.kCairoNormalFont,
    color: AppColors.kTextPrimaryColor,
  );
  static const TextStyle headlineLarge = TextStyle(
    fontSize: 15,
    // fontFamily: AppFonts.kCairoNormalFont,
    color: AppColors.kTextPrimaryColor,
  );

  static const TextStyle bodySmall = TextStyle(
    fontSize: 12,
    // fontFamily: AppFonts.kCairoNormalFont,
    fontWeight: FontWeight.normal,
    color: AppColors.kTextSecondaryColor,
  );

  // --- Button/Label Style ---
  static const TextStyle labelLarge = TextStyle(
    fontSize: 14,
    // fontFamily: AppFonts.kCairoBoldFont,
    color: Colors.white,
  );
}
