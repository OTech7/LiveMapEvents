import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';

import '../../../../../../core/constants/colors.dart';
import '../../../../../../core/strings/app_strings.dart';
import '../../../../domain/payload/register_payload.dart';
import '../../../bloc/auth_bloc.dart';

Widget buildRegisterButton(
    {required TextEditingController firstNameController,
    required TextEditingController lastNameController,
    required TextEditingController emailController,
    required GlobalKey<FormState> formKey,
    required TextEditingController passwordController}) {
  return BlocConsumer<AuthBloc, AuthState>(listener: (context, state) {
    if (state is AuthenticatedState) {
      context.go("/nav_screen");
    }
    if (state is AuthenticationErrorState) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(state.message),
          backgroundColor: Colors.red,
        ),
      );
    }
  }, builder: (context, state) {
    if (state is AuthenticationLoadingState) {
      return const Center(
          child: CircularProgressIndicator(
        color: AppColors.kPrimaryColor,
      ));
    }
    return ElevatedButton(
      onPressed: () {
        if (formKey.currentState!.validate()) {
          context.read<AuthBloc>().add(RegisterEvent(RegisterPayload(
                firstName: firstNameController.text,
                lastName: lastNameController.text,
                email: emailController.text,
                password: passwordController.text,
              )));
        }
      },
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.kPrimaryColor,
        padding: const EdgeInsets.symmetric(vertical: 16),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(10),
        ),
        elevation: 3,
      ),
      child: Text(AppStrings.register),
    );
  });
}
