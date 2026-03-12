import 'package:flutter/cupertino.dart';
import 'package:go_router/go_router.dart';

class CustomTransition extends CustomTransitionPage {
  CustomTransition({required super.child})
      : super(
          transitionDuration: const Duration(milliseconds: 400),
          reverseTransitionDuration: const Duration(milliseconds: 200),
          transitionsBuilder: (context, animation, secondaryAnimation, child) {
            return FadeTransition(
              opacity: animation,
              child: child,
            );
          },
        );
}
