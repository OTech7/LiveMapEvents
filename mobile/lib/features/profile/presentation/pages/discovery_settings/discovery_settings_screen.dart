import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import '../../../../../core/constants/colors.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../../domain/payload/discovery_settings_payload.dart';
import '../../bloc/profile_bloc.dart';
import '../profile_setup/widgets/step_indicator_widget.dart';
import '../../../../../core/widgets/custom_button.dart';

class DiscoverySettingsScreen extends StatefulWidget {
  final String firstName;
  final String lastName;
  final String phone;
  final String gender;
  final String dob;
  final double lat;
  final double lng;

  const DiscoverySettingsScreen({
    super.key,
    required this.firstName,
    required this.lastName,
    required this.phone,
    required this.gender,
    required this.dob,
    required this.lat,
    required this.lng,
  });

  @override
  State<DiscoverySettingsScreen> createState() =>
      _DiscoverySettingsScreenState();
}

class _DiscoverySettingsScreenState extends State<DiscoverySettingsScreen> {
  double _radius = 400.0;
  bool _notifyNearby = true;

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double hPad = size.width * 0.05;
    final double vSpaceLg = size.height * 0.035;
    final double vSpaceMd = size.height * 0.02;
    final double vSpaceSm = size.height * 0.01;

    return BlocListener<ProfileBloc, ProfileState>(
      listener: (context, state) {
        if (state is DiscoverySettingsSuccessState) {
          context.pushNamed(
            'personalize_feed',
            extra: {
              'firstName': widget.firstName,
              'lastName': widget.lastName,
              'phone': widget.phone,
              'gender': widget.gender,
              'dob': widget.dob,
              'lat': widget.lat,
              'lng': widget.lng,
              'radius': _radius,
              'notify': _notifyNearby,
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
          backgroundColor: Colors.white,
          elevation: 0,
          centerTitle: true,
          leading: IconButton(
            icon: const Icon(
              Icons.arrow_back,
              color: AppColors.kTextPrimaryColor,
            ),
            onPressed: () => context.pop(),
          ),
          title: const Text(
            "Discovery Settings",
            style: TextStyle(
              color: AppColors.kTextPrimaryColor,
              fontWeight: FontWeight.bold,
            ),
          ),
        ),
        body: SafeArea(
          child: Column(
            children: [
              Expanded(
                child: SingleChildScrollView(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Padding(
                        padding: EdgeInsets.symmetric(
                          horizontal: hPad,
                          vertical: vSpaceSm,
                        ),
                        child: const StepIndicatorWidget(
                          currentStep: 2,
                          totalSteps: 3,
                        ),
                      ),
                      // Map Preview
                      Container(
                        height: 250,
                        margin: EdgeInsets.all(hPad),
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(24),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.1),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(24),
                          child: GoogleMap(
                            initialCameraPosition: CameraPosition(
                              target: LatLng(widget.lat, widget.lng),
                              zoom: 14,
                            ),
                            markers: {
                              Marker(
                                markerId: const MarkerId('current_location'),
                                position: LatLng(widget.lat, widget.lng),
                              ),
                            },
                            circles: {
                              Circle(
                                circleId: const CircleId('radius'),
                                center: LatLng(widget.lat, widget.lng),
                                radius: _radius,
                                fillColor:
                                    AppColors.kPrimaryColor.withOpacity(0.2),
                                strokeColor: AppColors.kPrimaryColor,
                                strokeWidth: 2,
                              ),
                            },
                            myLocationButtonEnabled: false,
                            zoomControlsEnabled: false,
                          ),
                        ),
                      ),

                      Padding(
                        padding: EdgeInsets.symmetric(horizontal: hPad),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              "Discovery Radius",
                              style: TextStyle(
                                fontSize: 24,
                                fontWeight: FontWeight.bold,
                                color: AppColors.kTextPrimaryColor,
                              ),
                            ),
                            SizedBox(height: vSpaceSm),
                            const Text(
                              "Set the distance for finding events around you.",
                              style: TextStyle(
                                fontSize: 16,
                                color: AppColors.kTextSecondaryColor,
                              ),
                            ),
                            SizedBox(height: vSpaceLg),

                            // Radius Slider Card
                            Container(
                              padding: const EdgeInsets.all(20),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(24),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.05),
                                    blurRadius: 10,
                                    offset: const Offset(0, 4),
                                  ),
                                ],
                              ),
                              child: Column(
                                children: [
                                  Row(
                                    mainAxisAlignment:
                                        MainAxisAlignment.spaceBetween,
                                    children: [
                                      const Text(
                                        "Radius Distance",
                                        style: TextStyle(
                                          fontSize: 18,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                      Container(
                                        padding: const EdgeInsets.symmetric(
                                          horizontal: 12,
                                          vertical: 6,
                                        ),
                                        decoration: BoxDecoration(
                                          color: AppColors.kSelectedColor,
                                          borderRadius:
                                              BorderRadius.circular(12),
                                        ),
                                        child: Text(
                                          _radius >= 1000
                                              ? "${(_radius / 1000).toStringAsFixed(1)}km"
                                              : "${_radius.toInt()}m",
                                          style: const TextStyle(
                                            color: AppColors.kPrimaryColor,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  SizedBox(height: vSpaceMd),
                                  SliderTheme(
                                    data: SliderTheme.of(context).copyWith(
                                      activeTrackColor: AppColors.kPrimaryColor,
                                      inactiveTrackColor:
                                          Colors.grey.withOpacity(0.2),
                                      thumbColor: Colors.white,
                                      overlayColor: AppColors.kPrimaryColor
                                          .withOpacity(0.1),
                                      trackHeight: 8,
                                      thumbShape: const RoundSliderThumbShape(
                                        enabledThumbRadius: 12,
                                        elevation: 4,
                                      ),
                                    ),
                                    child: Slider(
                                      value: _radius,
                                      min: 200,
                                      max: 5000,
                                      onChanged: (value) {
                                        setState(() {
                                          _radius = value;
                                        });
                                      },
                                    ),
                                  ),
                                  const Padding(
                                    padding:
                                        EdgeInsets.symmetric(horizontal: 10),
                                    child: Row(
                                      mainAxisAlignment:
                                          MainAxisAlignment.spaceBetween,
                                      children: [
                                        Text("200m",
                                            style:
                                                TextStyle(color: Colors.grey)),
                                        Text("5km",
                                            style:
                                                TextStyle(color: Colors.grey)),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            SizedBox(height: vSpaceMd),

                            // Notification Toggle Card
                            Container(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 20,
                                vertical: 16,
                              ),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(20),
                                border: Border.all(
                                  color: Colors.grey.withOpacity(0.1),
                                ),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.notifications_outlined,
                                      color: Colors.grey),
                                  const SizedBox(width: 12),
                                  const Expanded(
                                    child: Text(
                                      "Notify for nearby events",
                                      style: TextStyle(
                                        fontSize: 16,
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ),
                                  Switch.adaptive(
                                    value: _notifyNearby,
                                    onChanged: (value) {
                                      setState(() {
                                        _notifyNearby = value;
                                      });
                                    },
                                    activeColor: AppColors.kPrimaryColor,
                                  ),
                                ],
                              ),
                            ),
                            SizedBox(height: vSpaceSm),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              // Save Button
              Padding(
                padding: EdgeInsets.all(hPad),
                child: BlocBuilder<ProfileBloc, ProfileState>(
                  builder: (context, state) {
                    return CustomButton(
                      text: "Save Changes",
                      onPressed: () {
                        final payload = DiscoverySettingsPayload(
                          radius: _radius.toInt(),
                          notify: _notifyNearby,
                        );
                        context
                            .read<ProfileBloc>()
                            .add(DiscoverySettingsEvent(payload));
                      },
                      isLoading: state is ProfileLoadingState,
                    );
                  },
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
