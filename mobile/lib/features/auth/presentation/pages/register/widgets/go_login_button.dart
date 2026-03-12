import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../../../../core/strings/app_strings.dart';

Widget buildLoginLink(BuildContext context) {
  return Row(
    mainAxisAlignment: MainAxisAlignment.center,
    children: [
      Text(
        AppStrings.alreadyHaveAccount,
        style: Theme.of(context).textTheme.bodyMedium,
      ),
      TextButton(
        onPressed: () {
          context.pop();
        },
        child: Text(AppStrings.login),
      ),
    ],
  );
}
