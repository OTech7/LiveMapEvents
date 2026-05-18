import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../core/constants/colors.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../../domain/entity/interest_entity.dart';
import '../../../domain/payload/complete_setup_payload.dart';
import '../../bloc/profile_bloc.dart';
import '../profile_setup/widgets/step_indicator_widget.dart';
import 'widgets/complete_setup_section_widget.dart';
import 'widgets/interests_grid_widget.dart';

class PersonalizeFeedScreen extends StatefulWidget {
  final String firstName;
  final String lastName;
  final String phone;
  final String gender;
  final String dob;
  final double lat;
  final double lng;

  const PersonalizeFeedScreen({
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
  State<PersonalizeFeedScreen> createState() => _PersonalizeFeedScreenState();
}

class _PersonalizeFeedScreenState extends State<PersonalizeFeedScreen> {
  final List<String> _selectedSlugs = [];
  List<InterestEntity> _availableInterests = [];

  @override
  void initState() {
    super.initState();
    context.read<ProfileBloc>().add(GetInterestsEvent());
  }

  void _onInterestTap(String slug) {
    setState(() {
      if (_selectedSlugs.contains(slug)) {
        _selectedSlugs.remove(slug);
      } else {
        _selectedSlugs.add(slug);
      }
    });
  }

  void _onComplete() {
    if (_selectedSlugs.length >= 3) {
      context.read<ProfileBloc>().add(SaveInterestsEvent(_selectedSlugs));
    }
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;
    final double hPad = size.width * 0.05;
    final double vSpaceLg = size.height * 0.035;
    final double vSpaceMd = size.height * 0.02;
    final double vSpaceSm = size.height * 0.01;

    return BlocListener<ProfileBloc, ProfileState>(
      listener: (context, state) {
        if (state is SetupCompletedState) {
          context.go('/home');
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
            AppStrings.personalizeFeed,
            style: Theme.of(context).textTheme.titleLarge,
          ),
          actions: [
            TextButton(
              onPressed: () {
                context.go('/home');
                // context.read<ProfileBloc>().add(SaveInterestsEvent(const []));
              },
              child: Text(
                AppStrings.skip,
                style: const TextStyle(
                  color: AppColors.kPrimaryColor,
                  fontWeight: FontWeight.bold,
                  fontSize: 16,
                ),
              ),
            ),
            const SizedBox(width: 8),
          ],
        ),
        body: SafeArea(
          child: Column(
            children: [
              Expanded(
                child: SingleChildScrollView(
                  padding: EdgeInsets.symmetric(
                    horizontal: hPad,
                    vertical: vSpaceSm,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const StepIndicatorWidget(currentStep: 3, totalSteps: 3),
                      SizedBox(height: vSpaceLg),
                      Text(
                        AppStrings.whatAreYouInto,
                        style: Theme.of(context).textTheme.headlineLarge
                            ?.copyWith(
                              fontWeight: FontWeight.w900,
                              color: AppColors.kTextPrimaryColor,
                            ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: vSpaceMd),
                      Padding(
                        padding: EdgeInsets.symmetric(
                          horizontal: size.width * 0.1,
                        ),
                        child: Text(
                          AppStrings.selectInterestsDesc,
                          style: Theme.of(context).textTheme.bodyMedium
                              ?.copyWith(fontSize: 15, height: 1.5),
                          textAlign: TextAlign.center,
                        ),
                      ),
                      SizedBox(height: vSpaceLg),
                      BlocBuilder<ProfileBloc, ProfileState>(
                        buildWhen: (prev, curr) =>
                            curr is InterestsLoadedState ||
                            curr is ProfileLoadingState ||
                            curr is ProfileErrorState,
                        builder: (context, state) {
                          if (state is ProfileLoadingState &&
                              _availableInterests.isEmpty) {
                            return const Center(
                              child: Padding(
                                padding: EdgeInsets.all(40.0),
                                child: CircularProgressIndicator(
                                  color: AppColors.kPrimaryColor,
                                ),
                              ),
                            );
                          }

                          if (state is InterestsLoadedState) {
                            _availableInterests = state.interests;
                          }

                          if (_availableInterests.isEmpty) {
                            return Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  Icon(
                                    Icons.category_outlined,
                                    size: 64,
                                    color: AppColors.kTextSecondaryColor
                                        .withOpacity(0.5),
                                  ),
                                  SizedBox(height: vSpaceMd),
                                  Text(
                                    state is ProfileErrorState
                                        ? state.message
                                        : AppStrings.noInterestsFound,
                                    style: Theme.of(context).textTheme.bodyLarge
                                        ?.copyWith(
                                          color: AppColors.kTextSecondaryColor,
                                        ),
                                    textAlign: TextAlign.center,
                                  ),
                                  SizedBox(height: vSpaceMd),
                                  ElevatedButton.icon(
                                    onPressed: () => context
                                        .read<ProfileBloc>()
                                        .add(GetInterestsEvent()),
                                    icon: const Icon(Icons.refresh),
                                    label: Text(AppStrings.retry),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: AppColors.kPrimaryColor,
                                      foregroundColor: Colors.white,
                                      shape: RoundedRectangleBorder(
                                        borderRadius: BorderRadius.circular(12),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            );
                          }

                          return InterestsGridWidget(
                            interests: _availableInterests,
                            selectedSlugs: _selectedSlugs,
                            onInterestTap: _onInterestTap,
                          );
                        },
                      ),
                    ],
                  ),
                ),
              ),
              BlocBuilder<ProfileBloc, ProfileState>(
                builder: (context, state) {
                  return CompleteSetupSectionWidget(
                    isLoading:
                        state is ProfileLoadingState &&
                        _availableInterests.isNotEmpty,
                    selectedCount: _selectedSlugs.length,
                    onComplete: _selectedSlugs.length >= 3 ? _onComplete : null,
                  );
                },
              ),
            ],
          ),
        ),
      ),
    );
  }
}
