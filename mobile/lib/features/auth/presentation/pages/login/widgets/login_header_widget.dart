import 'package:flutter/material.dart';

class LoginHeaderWidget extends StatelessWidget {
  const LoginHeaderWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 150,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
      ),
      child: Center(
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
