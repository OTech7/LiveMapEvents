import 'package:flutter/material.dart';
import '../assets/app_fonts.dart';
import '../constants/colors.dart';
import '../constants/text_styles.dart';

class AppTheme {
  static ThemeData theme = ThemeData(
    expansionTileTheme: ExpansionTileThemeData(
      collapsedShape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(10),
      ),
      collapsedBackgroundColor: Colors.white,
      backgroundColor: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
    ),
    checkboxTheme: const CheckboxThemeData(
      fillColor: WidgetStatePropertyAll(Colors.white),
      checkColor: WidgetStatePropertyAll(AppColors.kSelectedColor),
      side: BorderSide(color: AppColors.kSelectedColor),
    ),
    colorScheme: ColorScheme.light(primary: AppColors.kPrimaryColor),
    datePickerTheme: DatePickerThemeData(
      backgroundColor: AppColors.kBackgroundColor,
      headerBackgroundColor: AppColors.kPrimaryColor,
      confirmButtonStyle: const ButtonStyle(
        textStyle: WidgetStatePropertyAll(TextStyles.titleMedium),
      ),
      cancelButtonStyle: const ButtonStyle(
        textStyle: WidgetStatePropertyAll(TextStyles.labelLarge),
      ),
      todayBackgroundColor: WidgetStatePropertyAll(AppColors.kPrimaryColor),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
    ),
    listTileTheme: ListTileThemeData(
      tileColor: Colors.white,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      titleTextStyle: TextStyles.bodyLarge,
      subtitleTextStyle: TextStyles.bodyMedium,
    ),
    dividerTheme: DividerThemeData(
      color: AppColors.kLightGreyColor.withOpacity(.4),
    ),
    primaryColor: AppColors.kPrimaryColor,
    useMaterial3: true,
    scaffoldBackgroundColor: AppColors.kBackgroundColor,
    fontFamily: AppFonts.cairo,
    buttonTheme: ButtonThemeData(
      textTheme: ButtonTextTheme.primary,
      padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 10),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
    ),
    hoverColor: AppColors.kPrimaryColor.withOpacity(.2),
    progressIndicatorTheme: const ProgressIndicatorThemeData(
      // color: AppColors.kPrimaryColor,
    ),
    radioTheme: const RadioThemeData().copyWith(
      fillColor: WidgetStateProperty.all<Color>(AppColors.kSelectedColor),
    ),
    appBarTheme: AppBarTheme(
      iconTheme: const IconThemeData(color: Colors.white),
      backgroundColor: AppColors.kPrimaryColor,
      surfaceTintColor: Colors.transparent,
      centerTitle: true,
      titleTextStyle: TextStyles.titleLarge.copyWith(
        color: Colors.white,
        height: 0.2,
      ),
    ),
    dividerColor: AppColors.kLightGreyColor.withOpacity(.1),
    textTheme: const TextTheme(
      titleLarge: TextStyles.titleLarge,
      titleMedium: TextStyles.titleMedium,
      labelLarge: TextStyles.labelLarge,
      labelMedium: TextStyles.labelMedium,
      labelSmall: TextStyles.labelSmall,
      titleSmall: TextStyles.titleSmall,
      bodyLarge: TextStyles.bodyLarge,
      headlineMedium: TextStyles.headlineMedium,
      headlineLarge: TextStyles.headlineLarge,
      bodySmall: TextStyles.bodySmall,
      bodyMedium: TextStyles.bodyMedium,
    ),
    textButtonTheme: TextButtonThemeData(
      style: ButtonStyle(
        textStyle: WidgetStatePropertyAll(
          TextStyles.labelLarge.copyWith(color: AppColors.kSelectedColor),
        ),
      ),
    ),
    elevatedButtonTheme: ElevatedButtonThemeData(
      style: ElevatedButton.styleFrom(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        textStyle: TextStyles.labelLarge,
        foregroundColor: Colors.white,
        backgroundColor: AppColors.kSelectedColor,
      ),
    ),
    snackBarTheme: const SnackBarThemeData(
      contentTextStyle: TextStyles.labelLarge,
    ),
    inputDecorationTheme: InputDecorationTheme(
      // errorStyle: const TextStyle(color: Colors.red, fontSize: 0),
      border: OutlineInputBorder(
        borderSide: const BorderSide(color: Colors.white),
        borderRadius: BorderRadius.circular(10),
      ),
      hintStyle: TextStyles.bodyMedium,
      focusedBorder: OutlineInputBorder(
        borderSide: const BorderSide(color: Colors.white),
        borderRadius: BorderRadius.circular(10),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Colors.white),
      ),
      filled: true,
      outlineBorder: const BorderSide(color: Colors.white),
      disabledBorder: OutlineInputBorder(
        borderSide: const BorderSide(color: Colors.white),
        borderRadius: BorderRadius.circular(10),
      ),
      errorBorder: OutlineInputBorder(
        borderSide: const BorderSide(color: Colors.red),
        borderRadius: BorderRadius.circular(10),
      ),
      contentPadding: const EdgeInsets.only(
        left: 15,
        bottom: 0,
        top: 0,
        right: 15,
      ),
      fillColor: Colors.white,
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: AppColors.kPrimaryColor),
      ),
    ),
  );
}
