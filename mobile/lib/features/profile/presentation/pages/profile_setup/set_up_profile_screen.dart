import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../../../core/constants/colors.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../../../auth/presentation/pages/widgets/auth_fields.dart';
import '../../../domain/payload/complete_setup_payload.dart';
import '../../bloc/profile_bloc.dart';
import 'widgets/step_indicator_widget.dart';

class SetUpProfileScreen extends StatefulWidget {
  const SetUpProfileScreen({super.key});

  @override
  State<SetUpProfileScreen> createState() => _SetUpProfileScreenState();
}

class _SetUpProfileScreenState extends State<SetUpProfileScreen> {
  final TextEditingController _firstNameController = TextEditingController();
  final TextEditingController _lastNameController = TextEditingController();
  final TextEditingController _phoneController = TextEditingController();
  final TextEditingController _latController = TextEditingController(text: "0.0");
  final TextEditingController _lngController = TextEditingController(text: "0.0");
  
  String _selectedGender = 'male';
  DateTime? _selectedDob;

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _phoneController.dispose();
    _latController.dispose();
    _lngController.dispose();
    super.dispose();
  }

  Future<void> _selectDate(BuildContext context) async {
    final DateTime? picked = await showDatePicker(
      context: context,
      initialDate: DateTime(2000),
      firstDate: DateTime(1950),
      lastDate: DateTime.now(),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: AppColors.kPrimaryColor,
              onPrimary: Colors.white,
              onSurface: AppColors.kTextPrimaryColor,
            ),
          ),
          child: child!,
        );
      },
    );
    if (picked != null && picked != _selectedDob) {
      setState(() {
        _selectedDob = picked;
      });
    }
  }

  void _onComplete() {
    if (_firstNameController.text.isNotEmpty &&
        _lastNameController.text.isNotEmpty &&
        _phoneController.text.isNotEmpty &&
        _selectedDob != null) {
      
      final payload = CompleteSetupPayload(
        firstName: _firstNameController.text,
        lastName: _lastNameController.text,
        phone: _phoneController.text,
        gender: _selectedGender,
        dob: DateFormat('yyyy-MM-dd').format(_selectedDob!),
        lat: double.tryParse(_latController.text) ?? 0.0,
        lng: double.tryParse(_lngController.text) ?? 0.0,
      );
      
      context.read<ProfileBloc>().add(CompleteSetupEvent(payload));
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppStrings.requiredField)),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double hPad = size.width * 0.05;
    final double vSpaceLg = size.height * 0.03;
    final double vSpaceMd = size.height * 0.02;
    final double vSpaceSm = size.height * 0.01;

    return BlocListener<ProfileBloc, ProfileState>(
      listener: (context, state) {
        if (state is SetupCompletedState) {
          context.go('/home');
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text("Profile Setup Successful!")),
          );
        } else if (state is ProfileErrorState) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
                content: Text(state.message),
                backgroundColor: AppColors.kRedColor),
          );
        }
      },
      child: Scaffold(
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
                const StepIndicatorWidget(currentStep: 1, totalSteps: 1),
                SizedBox(height: vSpaceLg),

                // First Name field
                Text(AppStrings.firstName, style: Theme.of(context).textTheme.labelMedium),
                SizedBox(height: vSpaceSm),
                CustomTextFieldWidget(
                  controller: _firstNameController,
                  hintText: AppStrings.firstName,
                  icon: Icons.person_outline,
                ),
                SizedBox(height: vSpaceMd),

                // Last Name field
                Text(AppStrings.lastName, style: Theme.of(context).textTheme.labelMedium),
                SizedBox(height: vSpaceSm),
                CustomTextFieldWidget(
                  controller: _lastNameController,
                  hintText: AppStrings.lastName,
                  icon: Icons.person_outline,
                ),
                SizedBox(height: vSpaceMd),

                // Phone field
                Text(AppStrings.phone, style: Theme.of(context).textTheme.labelMedium),
                SizedBox(height: vSpaceSm),
                CustomTextFieldWidget(
                  controller: _phoneController,
                  hintText: AppStrings.phone,
                  icon: Icons.phone_android_outlined,
                  keyboardType: TextInputType.phone,
                ),
                SizedBox(height: vSpaceMd),

                // Gender field
                Text(AppStrings.gender, style: Theme.of(context).textTheme.labelMedium),
                SizedBox(height: vSpaceSm),
                Row(
                  children: [
                    Expanded(
                      child: RadioListTile<String>(
                        title: Text(AppStrings.male),
                        value: 'male',
                        groupValue: _selectedGender,
                        onChanged: (value) => setState(() => _selectedGender = value!),
                        activeColor: AppColors.kPrimaryColor,
                        contentPadding: EdgeInsets.zero,
                      ),
                    ),
                    Expanded(
                      child: RadioListTile<String>(
                        title: Text(AppStrings.female),
                        value: 'female',
                        groupValue: _selectedGender,
                        onChanged: (value) => setState(() => _selectedGender = value!),
                        activeColor: AppColors.kPrimaryColor,
                        contentPadding: EdgeInsets.zero,
                      ),
                    ),
                  ],
                ),
                SizedBox(height: vSpaceMd),

                // DOB field
                Text(AppStrings.dob, style: Theme.of(context).textTheme.labelMedium),
                SizedBox(height: vSpaceSm),
                InkWell(
                  onTap: () => _selectDate(context),
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_today_outlined, color: Colors.grey, size: 20),
                        const SizedBox(width: 12),
                        Text(
                          _selectedDob == null
                              ? AppStrings.selectDate
                              : DateFormat('yyyy-MM-dd').format(_selectedDob!),
                          style: TextStyle(
                            color: _selectedDob == null ? Colors.grey : AppColors.kTextPrimaryColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                SizedBox(height: vSpaceMd),

                // Lat/Lng
                Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(AppStrings.lat, style: Theme.of(context).textTheme.labelMedium),
                          SizedBox(height: vSpaceSm),
                          CustomTextFieldWidget(
                            controller: _latController,
                            hintText: "0.0",
                            icon: Icons.location_on_outlined,
                            keyboardType: TextInputType.number,
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(AppStrings.lng, style: Theme.of(context).textTheme.labelMedium),
                          SizedBox(height: vSpaceSm),
                          CustomTextFieldWidget(
                            controller: _lngController,
                            hintText: "0.0",
                            icon: Icons.location_on_outlined,
                            keyboardType: TextInputType.number,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                SizedBox(height: vSpaceLg),

                // Next Button (calls Complete Setup)
                BlocBuilder<ProfileBloc, ProfileState>(
                  builder: (context, state) {
                    return ElevatedButton(
                      onPressed: state is ProfileLoadingState ? null : _onComplete,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.kPrimaryColor,
                        foregroundColor: Colors.white,
                        padding: EdgeInsets.symmetric(vertical: size.height * 0.022),
                        elevation: 4,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
                      ),
                      child: state is ProfileLoadingState
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(
                                color: Colors.white,
                                strokeWidth: 2,
                              ),
                            )
                          : Row(
                              mainAxisAlignment: MainAxisAlignment.center,
                              children: [
                                Text(
                                  AppStrings.next,
                                  style: Theme.of(context).textTheme.labelLarge?.copyWith(fontSize: 18),
                                ),
                                const SizedBox(width: 8),
                                const Icon(Icons.arrow_forward_rounded, size: 20),
                              ],
                            ),
                    );
                  },
                ),
                SizedBox(height: vSpaceMd),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
