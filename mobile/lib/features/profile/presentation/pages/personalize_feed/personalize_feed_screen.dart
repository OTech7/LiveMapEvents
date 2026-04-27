import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../core/constants/colors.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../../domain/payload/complete_setup_payload.dart';
import '../../bloc/profile_bloc.dart';
import '../profile_setup/widgets/step_indicator_widget.dart';
import 'widgets/complete_setup_section_widget.dart';
import 'widgets/interests_grid_widget.dart';

class PersonalizeFeedScreen extends StatefulWidget {
  final String fullName;
  final String? bio;
  final String? profilePhoto;

  const PersonalizeFeedScreen({
    super.key,
    required this.fullName,
    this.bio,
    this.profilePhoto,
  });

  @override
  State<PersonalizeFeedScreen> createState() => _PersonalizeFeedScreenState();
}

class _PersonalizeFeedScreenState extends State<PersonalizeFeedScreen> {
  final List<int> _selectedIds = [];

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
      final payload = CompleteSetupPayload(
        fullName: widget.fullName,
        bio: widget.bio,
        profilePhoto: widget.profilePhoto,
        interestIds: _selectedIds,
      );
      // context.read<AuthBloc>().add(CompleteSetupEvent(payload));
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
          // TODO: Navigate to Home or Success screen
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
            icon:
                const Icon(Icons.arrow_back, color: AppColors.kTextPrimaryColor),
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
                  padding:
                      EdgeInsets.symmetric(horizontal: hPad, vertical: vSpaceSm),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const StepIndicatorWidget(currentStep: 2, totalSteps: 2),
                      SizedBox(height: vSpaceLg),
                      Text(
                        AppStrings.whatAreYouInto,
                        style: Theme.of(context)
                            .textTheme
                            .headlineLarge
                            ?.copyWith(
                              fontWeight: FontWeight.w900,
                              color: AppColors.kTextPrimaryColor,
                            ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: vSpaceMd),
                      Padding(
                        padding:
                            EdgeInsets.symmetric(horizontal: size.width * 0.1),
                        child: Text(
                          AppStrings.selectInterestsDesc,
                          style:
                              Theme.of(context).textTheme.bodyMedium?.copyWith(
                                    fontSize: 15,
                                    height: 1.5,
                                  ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                      SizedBox(height: vSpaceLg),
                      BlocBuilder<ProfileBloc, ProfileState>(
                        buildWhen: (prev, curr) =>
                            curr is InterestsLoadedState ||
                            curr is ProfileLoadingState,
                        builder: (context, state) {
                          if (state is ProfileLoadingState) {
                            return const Center(
                                child: Padding(
                              padding: EdgeInsets.all(40.0),
                              child: CircularProgressIndicator(),
                            ));
                          }

                          if (state is InterestsLoadedState) {
                            return InterestsGridWidget(
                              interests: state.interests,
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
              CompleteSetupSectionWidget(
                selectedCount: _selectedIds.length,
                onComplete: _selectedIds.length >= 3 ? _onComplete : null,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

