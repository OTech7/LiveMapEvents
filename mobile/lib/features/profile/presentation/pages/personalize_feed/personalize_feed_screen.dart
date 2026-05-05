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
  final List<int> _selectedIds = [];
  List<InterestEntity> _availableInterests = [];

  @override
  void initState() {
    super.initState();
    context.read<ProfileBloc>().add(GetInterestsEvent());
  }

  void _onInterestTap(int id) {
    setState(() {
      if (_selectedIds.contains(id)) {
        _selectedIds.remove(id);
      } else {
        _selectedIds.add(id);
      }
    });
  }

  void _onComplete() {
    if (_selectedIds.length >= 3) {
      final selectedNames = _availableInterests
          .where((interest) => _selectedIds.contains(interest.id))
          .map((interest) => interest.name)
          .toList();

      context.read<ProfileBloc>().add(SaveInterestsEvent(selectedNames));
    }
  }

  IconData _getIconData(String iconName) {
    switch (iconName.toLowerCase()) {
      case 'music':
        return Icons.music_note_rounded;
      case 'tech':
        return Icons.settings_input_component_rounded;
      case 'art':
        return Icons.palette_rounded;
      case 'sports':
        return Icons.sports_basketball_rounded;
      case 'food':
        return Icons.restaurant_rounded;
      case 'networking':
        return Icons.people_alt_rounded;
      case 'wellness':
        return Icons.self_improvement_rounded;
      case 'travel':
        return Icons.flight_rounded;
      case 'gaming':
        return Icons.sports_esports_rounded;
      case 'fashion':
        return Icons.checkroom_rounded;
      case 'business':
        return Icons.work_rounded;
      case 'film':
        return Icons.movie_rounded;
      default:
        return Icons.category_rounded;
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
          //  context.go('/home');
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text("Profile Setup Successful!")),
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
            AppStrings.personalizeFeed,
            style: Theme.of(context).textTheme.titleLarge,
          ),
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
                            curr is ProfileLoadingState,
                        builder: (context, state) {
                          if (state is ProfileLoadingState && _availableInterests.isEmpty) {
                            return const Center(
                              child: Padding(
                                padding: EdgeInsets.all(40.0),
                                child: CircularProgressIndicator(),
                              ),
                            );
                          }

                          if (state is InterestsLoadedState || _availableInterests.isNotEmpty) {
                            if (state is InterestsLoadedState) {
                              _availableInterests = state.interests;
                            }
                            return InterestsGridWidget(
                              interests: _availableInterests,
                              selectedIds: _selectedIds,
                              onInterestTap: _onInterestTap,
                              getIconData: _getIconData,
                            );
                          }

                          return const SizedBox();
                        },
                      ),
                    ],
                  ),
                ),
              ),
              BlocBuilder<ProfileBloc, ProfileState>(
                builder: (context, state) {
                  return CompleteSetupSectionWidget(
                    isLoading: state is ProfileLoadingState && _availableInterests.isNotEmpty,
                    selectedCount: _selectedIds.length,
                    onComplete: _selectedIds.length >= 3 ? _onComplete : null,
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
