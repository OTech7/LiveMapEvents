import 'package:flutter/material.dart';
import '../../../../domain/entity/interest_entity.dart';
import 'interest_card_widget.dart';

class InterestsGridWidget extends StatelessWidget {
  final List<InterestEntity> interests;
  final List<int> selectedIds;
  final Function(int) onInterestTap;
  final IconData Function(String) getIconData;

  const InterestsGridWidget({
    super.key,
    required this.interests,
    required this.selectedIds,
    required this.onInterestTap,
    required this.getIconData,
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
        final isSelected = selectedIds.contains(interest.id);

        return InterestCardWidget(
          name: interest.name,
          icon: getIconData(interest.icon),
          isSelected: isSelected,
          onTap: () => onInterestTap(interest.id),
        );
      },
    );
  }
}
