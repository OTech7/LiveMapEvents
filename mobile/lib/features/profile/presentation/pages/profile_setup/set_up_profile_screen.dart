import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';

import '../../../../../core/constants/colors.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../../../auth/presentation/pages/widgets/auth_fields.dart';
import 'widgets/bio_field_widget.dart';
import 'widgets/profile_photo_picker_widget.dart';
import 'widgets/step_indicator_widget.dart';

class SetUpProfileScreen extends StatefulWidget {
  const SetUpProfileScreen({super.key});

  @override
  State<SetUpProfileScreen> createState() => _SetUpProfileScreenState();
}

class _SetUpProfileScreenState extends State<SetUpProfileScreen> {
  final TextEditingController _fullNameController = TextEditingController();
  final TextEditingController _bioController = TextEditingController();

  @override
  void dispose() {
    _fullNameController.dispose();
    _bioController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double hPad = size.width * 0.05;
    final double vSpaceLg = size.height * 0.03;
    final double vSpaceMd = size.height * 0.02;
    final double vSpaceSm = size.height * 0.01;

    return Scaffold(
      backgroundColor: AppColors.kBackgroundColor,
      appBar: AppBar(
        backgroundColor: AppColors.kBackgroundColor,
        elevation: 0,
        centerTitle: true,
        leading: IconButton(
          icon: const Icon(
            Icons.arrow_back,
            color: AppColors.kTextPrimaryColor,
          ),
          onPressed: () => context.pop(),
        ),
        title: Text(
          AppStrings.setUpProfile,
          style: Theme.of(context).textTheme.titleLarge,
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: EdgeInsets.symmetric(horizontal: hPad, vertical: vSpaceSm),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const StepIndicatorWidget(currentStep: 1, totalSteps: 2),
              SizedBox(height: vSpaceLg),
              const ProfilePhotoPickerWidget(),
              SizedBox(height: vSpaceLg),

              // Full Name field
              Text(
                AppStrings.fullName,
                style: Theme.of(context).textTheme.labelMedium,
              ),
              SizedBox(height: vSpaceSm),
              CustomTextFieldWidget(
                controller: _fullNameController,
                hintText: AppStrings.eGName,
                icon: Icons.person_outline,
              ),
              SizedBox(height: vSpaceMd),

              // Bio field
              BioFieldWidget(controller: _bioController),
              SizedBox(height: vSpaceLg),

              // Bottom note
              Text(
                AppStrings.editDetailsLater,
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      fontStyle: FontStyle.italic,
                      height: 1.5,
                    ),
                textAlign: TextAlign.center,
              ),

              SizedBox(height: vSpaceLg),

              // Next Button
              ElevatedButton(
                onPressed: () {
                  context.pushNamed(
                    'personalize_feed',
                    extra: {
                      'fullName': _fullNameController.text,
                      'bio': _bioController.text,
                      'profilePhoto': null, // TODO: handle photo
                    },
                  );
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.kPrimaryColor,
                  foregroundColor: Colors.white,
                  padding: EdgeInsets.symmetric(
                    vertical: size.height * 0.022,
                  ),
                  elevation: 4,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(16),
                  ),
                ),
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      AppStrings.next,
                      style: Theme.of(context).textTheme.labelLarge?.copyWith(
                            fontSize: 18,
                          ),
                    ),
                    const SizedBox(width: 8),
                    const Icon(Icons.arrow_forward_rounded, size: 20),
                  ],
                ),
              ),
              SizedBox(height: vSpaceMd),
            ],
          ),
        ),
      ),
    );
  }
}

