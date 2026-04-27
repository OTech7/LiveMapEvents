import 'package:flutter/material.dart';

import '../../../../../../core/constants/colors.dart';

class LoginLogoWidget extends StatelessWidget {
  const LoginLogoWidget({super.key, required this.iconSize});

  final double iconSize;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Container(
        width: iconSize,
        height: iconSize,
        decoration: BoxDecoration(
          color: AppColors.kSelectedColor,
          shape: BoxShape.circle,
        ),
        child: Center(
          child: Icon(
            Icons.map,
            size: iconSize * 0.5,
            color: AppColors.kPrimaryColor,
          ),
        ),
      ),
    );
  }
}
