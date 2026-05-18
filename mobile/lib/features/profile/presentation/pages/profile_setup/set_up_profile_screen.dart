import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';

import '../../../../../core/constants/colors.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../../../auth/presentation/pages/widgets/auth_fields.dart';
import '../../../domain/payload/complete_setup_payload.dart';
import '../../bloc/profile_bloc.dart';
import '../../../../../core/widgets/custom_button.dart';
import 'widgets/profile_photo_picker_widget.dart';
import 'widgets/step_indicator_widget.dart';
import 'widgets/map_picker_screen.dart';

class SetUpProfileScreen extends StatefulWidget {
  const SetUpProfileScreen({super.key});

  @override
  State<SetUpProfileScreen> createState() => _SetUpProfileScreenState();
}

class _SetUpProfileScreenState extends State<SetUpProfileScreen> {
  final TextEditingController _firstNameController = TextEditingController();
  final TextEditingController _lastNameController = TextEditingController();

  // final TextEditingController _phoneController = TextEditingController();

  double _lat = 0.0;
  double _lng = 0.0;
  bool _isLocating = false;

  String _selectedGender = 'male';
  DateTime? _selectedDob;

  @override
  void initState() {
    super.initState();
    _getCurrentLocation();
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    // _phoneController.dispose();
    super.dispose();
  }

  Future<void> _getCurrentLocation() async {
    setState(() => _isLocating = true);
    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.whileInUse ||
          permission == LocationPermission.always) {
        Position position = await Geolocator.getCurrentPosition();
        setState(() {
          _lat = position.latitude;
          _lng = position.longitude;
        });
      }
    } catch (e) {
      debugPrint("Error getting location: $e");
    } finally {
      setState(() => _isLocating = false);
    }
  }

  Future<void> _selectLocationFromMap() async {
    final LatLng initialLocation = (_lat != 0.0 && _lng != 0.0)
        ? LatLng(_lat, _lng)
        : const LatLng(33.5138, 36.2765);

    final LatLng? pickedLocation = await Navigator.push<LatLng>(
      context,
      MaterialPageRoute(
        builder: (context) => MapPickerScreen(initialLocation: initialLocation),
      ),
    );

    if (pickedLocation != null) {
      setState(() {
        _lat = pickedLocation.latitude;
        _lng = pickedLocation.longitude;
      });
    }
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
        // _phoneController.text.isNotEmpty &&
        _selectedDob != null &&
        _lat != 0.0 &&
        _lng != 0.0) {
      final payload = CompleteSetupPayload(
        firstName: _firstNameController.text,
        lastName: _lastNameController.text,
        // phone: _phoneController.text,
        gender: _selectedGender,
        dob: DateFormat('yyyy-MM-dd').format(_selectedDob!),
        lat: _lat,
        lng: _lng,
      );

      context.read<ProfileBloc>().add(CompleteSetupEvent(payload));
    } else {
      String message = AppStrings.requiredField;
      if (_lat == 0.0 || _lng == 0.0) {
        message = AppStrings.pleaseSelectLocation;
      }
      ScaffoldMessenger.of(
        context,
      ).showSnackBar(SnackBar(content: Text(message)));
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
          context.pushNamed(
            'discovery_settings',
            extra: {
              'firstName': _firstNameController.text,
              'lastName': _lastNameController.text,
              'phone': '',
              'gender': _selectedGender,
              'dob': _selectedDob != null
                  ? DateFormat('yyyy-MM-dd').format(_selectedDob!)
                  : '',
              'lat': _lat,
              'lng': _lng,
            },
          );
        } else if (state is ProfileErrorState) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(state.message),
              backgroundColor: AppColors.kRedColor,
            ),
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
                const StepIndicatorWidget(currentStep: 1, totalSteps: 3),
                ProfilePhotoPickerWidget(),
                SizedBox(height: vSpaceMd),
                // First Name field
                Text(
                  AppStrings.firstName,
                  style: Theme.of(context).textTheme.labelMedium,
                ),
                SizedBox(height: vSpaceSm),
                CustomTextFieldWidget(
                  controller: _firstNameController,
                  hintText: AppStrings.firstName,
                  icon: Icons.person_outline,
                ),
                SizedBox(height: vSpaceMd),

                // Last Name field
                Text(
                  AppStrings.lastName,
                  style: Theme.of(context).textTheme.labelMedium,
                ),
                SizedBox(height: vSpaceSm),
                CustomTextFieldWidget(
                  controller: _lastNameController,
                  hintText: AppStrings.lastName,
                  icon: Icons.person_outline,
                ),
                SizedBox(height: vSpaceMd),

                // Phone field
                // Text(AppStrings.phone, style: Theme.of(context).textTheme.labelMedium),
                // SizedBox(height: vSpaceSm),
                // CustomTextFieldWidget(
                //   controller: _phoneController,
                //   hintText: AppStrings.phone,
                //   icon: Icons.phone_android_outlined,
                //   keyboardType: TextInputType.phone,
                // ),
                SizedBox(height: vSpaceMd),

                // Gender field
                Text(
                  AppStrings.gender,
                  style: Theme.of(context).textTheme.labelMedium,
                ),
                SizedBox(height: vSpaceSm),
                Row(
                  children: [
                    Expanded(
                      child: RadioListTile<String>(
                        title: Text(AppStrings.male),
                        value: 'male',
                        groupValue: _selectedGender,
                        onChanged: (value) =>
                            setState(() => _selectedGender = value!),
                        activeColor: AppColors.kPrimaryColor,
                        contentPadding: EdgeInsets.zero,
                      ),
                    ),
                    SizedBox(width: 12),
                    Expanded(
                      child: RadioListTile<String>(
                        title: Text(AppStrings.female),
                        value: 'female',
                        groupValue: _selectedGender,
                        onChanged: (value) =>
                            setState(() => _selectedGender = value!),
                        activeColor: AppColors.kPrimaryColor,
                        contentPadding: EdgeInsets.zero,
                      ),
                    ),
                  ],
                ),
                SizedBox(height: vSpaceMd),

                // DOB field
                Text(
                  AppStrings.dob,
                  style: Theme.of(context).textTheme.labelMedium,
                ),
                SizedBox(height: vSpaceSm),
                InkWell(
                  onTap: () => _selectDate(context),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 16,
                      vertical: 16,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: Colors.grey.shade300),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.calendar_today_outlined,
                          color: Colors.grey,
                          size: 20,
                        ),
                        const SizedBox(width: 12),
                        Text(
                          _selectedDob == null
                              ? AppStrings.selectDate
                              : DateFormat('yyyy-MM-dd').format(_selectedDob!),
                          style: TextStyle(
                            color: _selectedDob == null
                                ? Colors.grey
                                : AppColors.kTextPrimaryColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
                SizedBox(height: vSpaceMd),

                // Location Picker
                Text(
                  AppStrings.location,
                  style: Theme.of(context).textTheme.labelMedium,
                ),
                SizedBox(height: vSpaceSm),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: Colors.grey.shade300),
                  ),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          const Icon(
                            Icons.location_on_outlined,
                            color: AppColors.kPrimaryColor,
                            size: 24,
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  _lat == 0.0 && _lng == 0.0
                                      ? AppStrings.noLocationSelected
                                      : AppStrings.locationSelected,
                                  style: TextStyle(
                                    color: _lat == 0.0
                                        ? Colors.grey
                                        : AppColors.kTextPrimaryColor,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                                if (_isLocating)
                                  const Padding(
                                    padding: EdgeInsets.only(top: 4.0),
                                    child: LinearProgressIndicator(
                                      minHeight: 2,
                                      backgroundColor: Colors.transparent,
                                      valueColor: AlwaysStoppedAnimation<Color>(
                                        AppColors.kPrimaryColor,
                                      ),
                                    ),
                                  ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      Row(
                        children: [
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: _isLocating
                                  ? null
                                  : _getCurrentLocation,
                              icon: const Icon(Icons.my_location, size: 18),
                              label: Text(AppStrings.useMyLocation),
                              style: OutlinedButton.styleFrom(
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                side: BorderSide(color: Colors.grey.shade300),
                              ),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: OutlinedButton.icon(
                              onPressed: _selectLocationFromMap,
                              icon: const Icon(Icons.map, size: 18),
                              label: Text(AppStrings.pickFromMap),
                              style: OutlinedButton.styleFrom(
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(12),
                                ),
                                side: BorderSide(color: Colors.grey.shade300),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),

                SizedBox(height: vSpaceLg),

                // Next Button (calls Complete Setup)
                BlocBuilder<ProfileBloc, ProfileState>(
                  builder: (context, state) {
                    return CustomButton(
                      text: AppStrings.next,
                      onPressed: _onComplete,
                      isLoading: state is ProfileLoadingState,
                      icon: Icons.arrow_forward_rounded,
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
