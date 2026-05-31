import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../../../../../../core/data/countries.dart';
import '../../../../../../core/helper/validator.dart';
import '../../../../../../core/strings/app_strings.dart';
import '../../../../../../core/widgets/country_code_picker.dart';

class LoginFieldsWidget extends StatelessWidget {
  const LoginFieldsWidget({
    super.key,
    required this.selectedCountry,
    required this.onCountryChanged,
    required this.size,
    required TextEditingController phoneController,
  }) : _phoneController = phoneController;

  final Country selectedCountry;
  final ValueChanged<Country> onCountryChanged;
  final Size size;
  final TextEditingController _phoneController;

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CountryCodePicker(
          selected: selectedCountry,
          onChanged: onCountryChanged,
          style: CountryCodePickerStyle.field,
        ),
        SizedBox(width: size.width * 0.03),
        Expanded(
          child: TextFormField(
            controller: _phoneController,
            keyboardType: TextInputType.phone,
            inputFormatters: [
              FilteringTextInputFormatter.digitsOnly,
              LengthLimitingTextInputFormatter(15),
            ],
            decoration: InputDecoration(
              filled: true,
              fillColor: Theme
                  .of(context)
                  .inputDecorationTheme
                  .fillColor,
              hintText: AppStrings.enterMobileNumber,
              hintStyle: TextStyle(color: Colors.grey.shade400),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(15),
                borderSide: BorderSide(color: Colors.grey.shade300),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(15),
                borderSide: BorderSide(
                  color: Theme
                      .of(context)
                      .colorScheme
                      .primary,
                ),
              ),
            ),
            validator: AppValidator.phoneNumberValidator,
          ),
        ),
      ],
    );
  }
}
