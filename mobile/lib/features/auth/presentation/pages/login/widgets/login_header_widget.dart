import 'package:flutter/material.dart';

class LoginHeaderWidget extends StatelessWidget {
  const LoginHeaderWidget({super.key});

  @override
  Widget build(BuildContext context) {
    final double headerHeight = (MediaQuery.of(context).size.height * 0.12)
        .clamp(80.0, 150.0);
    return Container(
      height: headerHeight,
      decoration: BoxDecoration(borderRadius: BorderRadius.circular(20)),
      child: const Center(
        // child: SvgPicture.asset(
        //   AppImages.logo,
        //   height: 120,
        //   colorFilter: const ColorFilter.mode(
        //     Colors.white,
        //     BlendMode.srcIn,
        //   ),
        // ),
      ),
    );
  }
}
