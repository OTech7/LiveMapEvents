import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:go_router/go_router.dart';
import '../../../../../core/constants/colors.dart';
import '../../../../../core/strings/app_strings.dart';
import '../../../domain/payload/complete_setup_payload.dart';
import '../../bloc/profile_bloc.dart';
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
      case 'music': return Icons.music_note_rounded;
      case 'tech': return Icons.settings_input_component_rounded;
      case 'art': return Icons.palette_rounded;
      case 'sports': return Icons.sports_basketball_rounded;
      case 'food': return Icons.restaurant_rounded;
      case 'networking': return Icons.people_alt_rounded;
      case 'wellness': return Icons.self_improvement_rounded;
      case 'travel': return Icons.flight_rounded;
      case 'gaming': return Icons.sports_esports_rounded;
      case 'fashion': return Icons.checkroom_rounded;
      case 'business': return Icons.work_rounded;
      case 'film': return Icons.movie_rounded;
      default: return Icons.category_rounded;
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
            SnackBar(content: Text(state.message), backgroundColor: AppColors.kRedColor),
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
            icon: const Icon(Icons.arrow_back, color: AppColors.kTextPrimaryColor),
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
                  padding: EdgeInsets.symmetric(horizontal: hPad, vertical: vSpaceSm),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Progress Bar
                      Row(
                        children: [
                          Expanded(
                            child: Container(
                              height: 6,
                              decoration: BoxDecoration(
                                color: AppColors.kPrimaryColor,
                                borderRadius: BorderRadius.circular(4),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Container(
                              height: 6,
                              decoration: BoxDecoration(
                                color: AppColors.kPrimaryColor,
                                borderRadius: BorderRadius.circular(4),
                              ),
                            ),
                          ),
                        ],
                      ),
                      SizedBox(height: vSpaceSm),
                      Text(
                        AppStrings.step2of2,
                        style: Theme.of(context).textTheme.labelSmall,
                      ),
                      SizedBox(height: vSpaceLg),

                      Text(
                        AppStrings.whatAreYouInto,
                        style: Theme.of(context).textTheme.headlineLarge?.copyWith(
                          fontWeight: FontWeight.w900,
                          color: AppColors.kTextPrimaryColor,
                        ),
                        textAlign: TextAlign.center,
                      ),
                      SizedBox(height: vSpaceMd),
                      Padding(
                        padding: EdgeInsets.symmetric(horizontal: size.width * 0.1),
                        child: Text(
                          AppStrings.selectInterestsDesc,
                          style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                            fontSize: 15,
                            height: 1.5,
                          ),
                          textAlign: TextAlign.center,
                        ),
                      ),
                      SizedBox(height: vSpaceLg),

                      BlocBuilder<ProfileBloc, ProfileState>(
                        buildWhen: (prev, curr) => curr is InterestsLoadedState || curr is ProfileLoadingState,
                        builder: (context, state) {
                          if (state is ProfileLoadingState) {
                            return const Center(child: Padding(
                              padding: EdgeInsets.all(40.0),
                              child: CircularProgressIndicator(),
                            ));
                          }

                          if (state is InterestsLoadedState) {
                            return GridView.builder(
                              shrinkWrap: true,
                              physics: const NeverScrollableScrollPhysics(),
                              gridDelegate:  SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 2,
                                crossAxisSpacing: 16,
                                mainAxisSpacing: 16,
                                childAspectRatio: 1.1,
                              ),
                              itemCount: state.interests.length,
                              itemBuilder: (context, index) {
                                final interest = state.interests[index];
                                final isSelected = _selectedIds.contains(interest.id);

                                return GestureDetector(
                                  onTap: () => _onInterestTap(interest.id),
                                  child: AnimatedContainer(
                                    duration: const Duration(milliseconds: 200),
                                    decoration: BoxDecoration(
                                      color: isSelected ? AppColors.kPrimaryColor.withOpacity(0.08) : Colors.white,
                                      borderRadius: BorderRadius.circular(20),
                                      border: Border.all(
                                        color: isSelected ? AppColors.kPrimaryColor : Colors.grey.withOpacity(0.1),
                                        width: 2,
                                      ),
                                      boxShadow: [
                                        if (isSelected)
                                          BoxShadow(
                                            color: AppColors.kPrimaryColor.withOpacity(0.1),
                                            blurRadius: 10,
                                            offset: const Offset(0, 4),
                                          ),
                                      ],
                                    ),
                                    child: Column(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Icon(
                                          _getIconData(interest.icon),
                                          size: 32,
                                          color: isSelected ? AppColors.kPrimaryColor : Colors.grey.shade700,
                                        ),
                                        const SizedBox(height: 12),
                                        Text(
                                          interest.name,
                                          style: Theme.of(context).textTheme.titleSmall?.copyWith(
                                            color: isSelected ? AppColors.kPrimaryColor : AppColors.kTextPrimaryColor,
                                            fontWeight: isSelected ? FontWeight.w800 : FontWeight.w600,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                );
                              },
                            );
                          }

                          return const SizedBox();
                        },
                      ),
                    ],
                  ),
                ),
              ),
              
              // Bottom Action Section
              Container(
                padding: EdgeInsets.all(hPad),
                decoration: BoxDecoration(
                  color: Colors.white,
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.05),
                      blurRadius: 10,
                      offset: const Offset(0, -5),
                    ),
                  ],
                ),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    ElevatedButton(
                      onPressed: _selectedIds.length >= 3 ? _onComplete : null,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.kPrimaryColor,
                        foregroundColor: Colors.white,
                        disabledBackgroundColor: Colors.grey.shade300,
                        padding: EdgeInsets.symmetric(vertical: size.height * 0.022),
                        elevation: 4,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                        ),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Text(
                            "Complete Setup",
                            style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                          ),
                          const SizedBox(width: 8),
                          Icon(
                            _selectedIds.length >= 3 ? Icons.check_circle_rounded : Icons.check_circle_outline_rounded,
                            size: 20,
                          ),
                        ],
                      ),
                    ),
                    SizedBox(height: vSpaceSm),
                    Text(
                      AppStrings.selectedCount.replaceFirst("{}", _selectedIds.length.toString()),
                      style: Theme.of(context).textTheme.bodySmall?.copyWith(
                        color: _selectedIds.length >= 3 ? AppColors.kPrimaryColor : AppColors.kTextSecondaryColor,
                        fontWeight: _selectedIds.length >= 3 ? FontWeight.bold : FontWeight.normal,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
