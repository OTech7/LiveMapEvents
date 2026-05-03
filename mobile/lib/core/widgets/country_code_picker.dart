import 'package:flutter/material.dart';

import '../constants/colors.dart';
import '../data/countries.dart';

/// A compact button that shows the currently-selected country (flag + dial
/// code) and opens a searchable bottom sheet on tap.
///
/// Use this together with a phone-number `TextFormField`. Wire the dial code
/// into your auth payload via [selected.dialCode].
class CountryCodePicker extends StatelessWidget {
  final Country selected;
  final ValueChanged<Country> onChanged;

  /// Display style: "field" makes the picker look like a TextFormField so it
  /// lines up with the phone input. "compact" is a smaller chip variant.
  final CountryCodePickerStyle style;

  const CountryCodePicker({
    super.key,
    required this.selected,
    required this.onChanged,
    this.style = CountryCodePickerStyle.field,
  });

  Future<void> _open(BuildContext context) async {
    final picked = await showModalBottomSheet<Country>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => _CountryListSheet(selectedCode: selected.code),
    );
    if (picked != null) onChanged(picked);
  }

  @override
  Widget build(BuildContext context) {
    final isField = style == CountryCodePickerStyle.field;
    return InkWell(
      onTap: () => _open(context),
      borderRadius: BorderRadius.circular(isField ? 15 : 12),
      child: Container(
        padding: EdgeInsets.symmetric(
          horizontal: 12,
          vertical: isField ? 16 : 10,
        ),
        decoration: BoxDecoration(
          color: AppColors.kBackgroundColor,
          borderRadius: BorderRadius.circular(isField ? 15 : 12),
          border: Border.all(color: Colors.grey.shade300),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Text(selected.flag, style: const TextStyle(fontSize: 20)),
            const SizedBox(width: 6),
            Text(
              selected.dialCode,
              style: const TextStyle(
                fontWeight: FontWeight.w600,
                color: AppColors.kTextPrimaryColor,
              ),
            ),
            const SizedBox(width: 2),
            const Icon(Icons.arrow_drop_down, color: AppColors.kLightGreyColor),
          ],
        ),
      ),
    );
  }
}

enum CountryCodePickerStyle { field, compact }

class _CountryListSheet extends StatefulWidget {
  final String selectedCode;

  const _CountryListSheet({required this.selectedCode});

  @override
  State<_CountryListSheet> createState() => _CountryListSheetState();
}

class _CountryListSheetState extends State<_CountryListSheet> {
  String _query = '';

  @override
  Widget build(BuildContext context) {
    final mq = MediaQuery.of(context);
    // Build the list: pin the default country (Syria) to the top, then
    // show every other country in alphabetical order.
    final base = [...Countries.all]..sort((a, b) => a.name.compareTo(b.name));
    final ordered = <Country>[
      Countries.defaultCountry,
      ...base.where((c) => c.code != Countries.defaultCountry.code),
    ];
    final filtered = ordered.where((c) => c.matches(_query)).toList();

    return Padding(
      padding: EdgeInsets.only(bottom: mq.viewInsets.bottom),
      child: SizedBox(
        height: mq.size.height * 0.75,
        child: Column(
          children: [
            const SizedBox(height: 10),
            Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.shade300,
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: 12),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 16),
              child: TextField(
                autofocus: false,
                onChanged: (v) => setState(() => _query = v),
                decoration: InputDecoration(
                  hintText: 'Search country or code',
                  prefixIcon: const Icon(Icons.search),
                  filled: true,
                  fillColor: AppColors.kBackgroundColor,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide.none,
                  ),
                  contentPadding: const EdgeInsets.symmetric(vertical: 0),
                ),
              ),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: filtered.isEmpty
                  ? const Center(child: Text('No matches'))
                  : ListView.separated(
                itemCount: filtered.length,
                separatorBuilder: (_, __) =>
                    Divider(height: 1, color: Colors.grey.shade100),
                itemBuilder: (_, i) {
                  final c = filtered[i];
                  final isSelected = c.code == widget.selectedCode;
                  return ListTile(
                    leading: Text(
                      c.flag,
                      style: const TextStyle(fontSize: 24),
                    ),
                    title: Text(c.name),
                    trailing: Text(
                      c.dialCode,
                      style: TextStyle(
                        color: isSelected
                            ? AppColors.kPrimaryColor
                            : AppColors.kTextSecondaryColor,
                        fontWeight: isSelected
                            ? FontWeight.w700
                            : FontWeight.w500,
                      ),
                    ),
                    selected: isSelected,
                    selectedTileColor:
                    AppColors.kPrimaryColor.withOpacity(0.06),
                    onTap: () => Navigator.of(context).pop(c),
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
