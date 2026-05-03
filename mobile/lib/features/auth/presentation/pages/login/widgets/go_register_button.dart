import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../../../../core/strings/app_strings.dart';

class GoRegisterButton extends StatelessWidget {
  const GoRegisterButton({super.key});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Text(AppStrings.dontHaveAccount,
            style: Theme.of(context).textTheme.bodyMedium),
        TextButton(
          onPressed: () {
            context.pushNamed("register");
          },
          child: Text(AppStrings.register,
              style: Theme.of(context).textTheme.bodyLarge),
        ),
      ],
    );
  }
}
