import 'package:flutter/material.dart';

import 'colors.dart';
import 'fonts.dart';

class TextStyles {
  // --- Title Styles (Bold weight) ---
  static const TextStyle titleLarge = TextStyle(
    fontSize: 20,
    fontFamily: AppFonts.cairo,
    fontWeight: FontWeight.w700,
    color: AppColors.kTextPrimaryColor,
  );

  static const TextStyle titleMedium = TextStyle(
    fontSize: 18,
    fontFamily: AppFonts.cairo,
    fontWeight: FontWeight.w700,
    color: AppColors.kTextPrimaryColor,
  );

  static const TextStyle titleSmall = TextStyle(
    fontSize: 16,
    fontFamily: AppFonts.cairo,
    fontWeight: FontWeight.w700,
    color: AppColors.kTextPrimaryColor,
  );

  // --- Headline Styles (Display / RobotoSlab) ---
  static const TextStyle headlineLarge = TextStyle(
    fontSize: 28,
    fontFamily: AppFonts.robotoSlab,
    fontWeight: FontWeight.w700,
    color: AppColors.kTextPrimaryColor,
  );

  static const TextStyle headlineMedium = TextStyle(
    fontSize: 22,
    fontFamily: AppFonts.robotoSlab,
    fontWeight: FontWeight.w700,
    color: AppColors.kTextPrimaryColor,
  );

  // --- Body Styles (Normal/Medium Weight) ---
  static const TextStyle bodyLarge = TextStyle(
    fontSize: 16,
    fontFamily: AppFonts.cairo,
    fontWeight: FontWeight.w500,
    color: AppColors.kTextPrimaryColor,
  );

  static const TextStyle bodyMedium = TextStyle(
    fontSize: 13,
    fontFamily: AppFonts.cairo,
    color: AppColors.kTextSecondaryColor,
  );

  static const TextStyle bodySmall = TextStyle(
    fontSize: 12,
    fontFamily: AppFonts.cairo,
    fontWeight: FontWeight.normal,
    color: AppColors.kTextSecondaryColor,
  );

  // --- Button / Label Style ---
  static const TextStyle labelLarge = TextStyle(
    fontSize: 14,
    fontFamily: AppFonts.cairo,
    fontWeight: FontWeight.w700,
    color: Colors.white,
  );
}
