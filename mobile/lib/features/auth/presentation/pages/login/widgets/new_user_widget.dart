import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../../../../core/constants/colors.dart';
import '../../../../../../core/strings/app_strings.dart';

class NewUserWidget extends StatelessWidget {
  const NewUserWidget({super.key});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Text(
          AppStrings.newToEventMap,
          style: Theme.of(context).textTheme.bodyMedium,
        ),
        GestureDetector(
          onTap: () {
            context.push('/register');
          },
          child: Text(
            AppStrings.createAnAccountText,
            style: Theme.of(context).textTheme.bodyLarge?.copyWith(
              color: AppColors.kPrimaryColor,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
      ],
    );
  }
}
