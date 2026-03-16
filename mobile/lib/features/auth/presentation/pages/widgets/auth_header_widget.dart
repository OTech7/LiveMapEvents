import 'package:flutter/material.dart';

import '../../../../../../core/constants/colors.dart';

class AuthHeaderWidget extends StatelessWidget {
  const AuthHeaderWidget({super.key});

  @override
  Widget build(BuildContext context) {
    final double headerHeight =
        (MediaQuery.of(context).size.height * 0.15).clamp(100.0, 180.0);
    return Container(
      height: headerHeight,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [
            AppColors.kPrimaryColor,
            AppColors.kPrimaryColor,
          ],
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: AppColors.kPrimaryColor.withOpacity(0.3),
            blurRadius: 20,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      // child: Center(
      //   child: SvgPicture.asset(
      //     AppImages.logo,
      //     height: 120,
      //     colorFilter: const ColorFilter.mode(
      //       Colors.white,
      //       BlendMode.srcIn,
      //     ),
      //   ),
      // ),
    );
  }
}
