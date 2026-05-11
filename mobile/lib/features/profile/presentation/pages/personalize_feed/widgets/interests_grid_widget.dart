import 'package:flutter/material.dart';
import '../../../../domain/entity/interest_entity.dart';
import 'interest_card_widget.dart';

class InterestsGridWidget extends StatelessWidget {
  final List<InterestEntity> interests;
  final List<String> selectedSlugs;
  final Function(String) onInterestTap;

  const InterestsGridWidget({
    super.key,
    required this.interests,
    required this.selectedSlugs,
    required this.onInterestTap,
  });

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: 16,
        mainAxisSpacing: 16,
        childAspectRatio: 1.1,
      ),
      itemCount: interests.length,
      itemBuilder: (context, index) {
        final interest = interests[index];
        final isSelected = selectedSlugs.contains(interest.slug);

        return InterestCardWidget(
          name: interest.name,
          icon: Icons.category_rounded,
          isSelected: isSelected,
          onTap: () => onInterestTap(interest.slug),
        );
      },
    );
  }
}
