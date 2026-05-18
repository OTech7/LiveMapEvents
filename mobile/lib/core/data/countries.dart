/// Country dial-code data used by the phone-number country picker.
///
/// Flag emoji is generated on the fly from the ISO 3166-1 alpha-2 code so we
/// don't ship any image assets. (Windows desktop falls back to letters; on
/// iOS, Android and modern web the proper flag glyph renders.)
class Country {
  final String code; // ISO 3166-1 alpha-2 (e.g. "SY")
  final String name;
  final String dialCode; // E.164 prefix incl. "+", e.g. "+963"

  const Country({
    required this.code,
    required this.name,
    required this.dialCode,
  });

  /// Regional-indicator flag emoji built from the ISO code.
  String get flag {
    final upper = code.toUpperCase();
    if (upper.length != 2) return '';
    const base = 0x1F1E6; // 🇦
    return String.fromCharCodes([
      base + (upper.codeUnitAt(0) - 0x41),
      base + (upper.codeUnitAt(1) - 0x41),
    ]);
  }

  bool matches(String query) {
    if (query.isEmpty) return true;
    final q = query.trim().toLowerCase();
    return name.toLowerCase().contains(q) ||
        code.toLowerCase().contains(q) ||
        dialCode.contains(q.replaceAll('+', ''));
  }
}

class Countries {
  /// Default country shown when the user opens the phone field.
  static const Country defaultCountry = Country(
    code: 'SY',
    name: 'Syria',
    dialCode: '+963',
  );

  /// Curated list — covers MENA, Europe, the Americas, and major APAC.
  /// Sorted alphabetically by [name] for the picker; the picker pins
  /// [defaultCountry] to the top.
  static const List<Country> all = [
    Country(code: 'AF', name: 'Afghanistan', dialCode: '+93'),
    Country(code: 'AL', name: 'Albania', dialCode: '+355'),
    Country(code: 'DZ', name: 'Algeria', dialCode: '+213'),
    Country(code: 'AR', name: 'Argentina', dialCode: '+54'),
    Country(code: 'AM', name: 'Armenia', dialCode: '+374'),
    Country(code: 'AU', name: 'Australia', dialCode: '+61'),
    Country(code: 'AT', name: 'Austria', dialCode: '+43'),
    Country(code: 'AZ', name: 'Azerbaijan', dialCode: '+994'),
    Country(code: 'BH', name: 'Bahrain', dialCode: '+973'),
    Country(code: 'BD', name: 'Bangladesh', dialCode: '+880'),
    Country(code: 'BY', name: 'Belarus', dialCode: '+375'),
    Country(code: 'BE', name: 'Belgium', dialCode: '+32'),
    Country(code: 'BR', name: 'Brazil', dialCode: '+55'),
    Country(code: 'BG', name: 'Bulgaria', dialCode: '+359'),
    Country(code: 'CA', name: 'Canada', dialCode: '+1'),
    Country(code: 'CL', name: 'Chile', dialCode: '+56'),
    Country(code: 'CN', name: 'China', dialCode: '+86'),
    Country(code: 'CO', name: 'Colombia', dialCode: '+57'),
    Country(code: 'HR', name: 'Croatia', dialCode: '+385'),
    Country(code: 'CY', name: 'Cyprus', dialCode: '+357'),
    Country(code: 'CZ', name: 'Czechia', dialCode: '+420'),
    Country(code: 'DK', name: 'Denmark', dialCode: '+45'),
    Country(code: 'EG', name: 'Egypt', dialCode: '+20'),
    Country(code: 'EE', name: 'Estonia', dialCode: '+372'),
    Country(code: 'FI', name: 'Finland', dialCode: '+358'),
    Country(code: 'FR', name: 'France', dialCode: '+33'),
    Country(code: 'GE', name: 'Georgia', dialCode: '+995'),
    Country(code: 'DE', name: 'Germany', dialCode: '+49'),
    Country(code: 'GR', name: 'Greece', dialCode: '+30'),
    Country(code: 'HK', name: 'Hong Kong', dialCode: '+852'),
    Country(code: 'HU', name: 'Hungary', dialCode: '+36'),
    Country(code: 'IS', name: 'Iceland', dialCode: '+354'),
    Country(code: 'IN', name: 'India', dialCode: '+91'),
    Country(code: 'ID', name: 'Indonesia', dialCode: '+62'),
    Country(code: 'IR', name: 'Iran', dialCode: '+98'),
    Country(code: 'IQ', name: 'Iraq', dialCode: '+964'),
    Country(code: 'IE', name: 'Ireland', dialCode: '+353'),
    Country(code: 'IT', name: 'Italy', dialCode: '+39'),
    Country(code: 'JP', name: 'Japan', dialCode: '+81'),
    Country(code: 'JO', name: 'Jordan', dialCode: '+962'),
    Country(code: 'KZ', name: 'Kazakhstan', dialCode: '+7'),
    Country(code: 'KE', name: 'Kenya', dialCode: '+254'),
    Country(code: 'KW', name: 'Kuwait', dialCode: '+965'),
    Country(code: 'KG', name: 'Kyrgyzstan', dialCode: '+996'),
    Country(code: 'LV', name: 'Latvia', dialCode: '+371'),
    Country(code: 'LB', name: 'Lebanon', dialCode: '+961'),
    Country(code: 'LY', name: 'Libya', dialCode: '+218'),
    Country(code: 'LT', name: 'Lithuania', dialCode: '+370'),
    Country(code: 'LU', name: 'Luxembourg', dialCode: '+352'),
    Country(code: 'MY', name: 'Malaysia', dialCode: '+60'),
    Country(code: 'MT', name: 'Malta', dialCode: '+356'),
    Country(code: 'MX', name: 'Mexico', dialCode: '+52'),
    Country(code: 'MD', name: 'Moldova', dialCode: '+373'),
    Country(code: 'MA', name: 'Morocco', dialCode: '+212'),
    Country(code: 'NL', name: 'Netherlands', dialCode: '+31'),
    Country(code: 'NZ', name: 'New Zealand', dialCode: '+64'),
    Country(code: 'NG', name: 'Nigeria', dialCode: '+234'),
    Country(code: 'NO', name: 'Norway', dialCode: '+47'),
    Country(code: 'OM', name: 'Oman', dialCode: '+968'),
    Country(code: 'PK', name: 'Pakistan', dialCode: '+92'),
    Country(code: 'PS', name: 'Palestine', dialCode: '+970'),
    Country(code: 'PH', name: 'Philippines', dialCode: '+63'),
    Country(code: 'PL', name: 'Poland', dialCode: '+48'),
    Country(code: 'PT', name: 'Portugal', dialCode: '+351'),
    Country(code: 'QA', name: 'Qatar', dialCode: '+974'),
    Country(code: 'RO', name: 'Romania', dialCode: '+40'),
    Country(code: 'RU', name: 'Russia', dialCode: '+7'),
    Country(code: 'SA', name: 'Saudi Arabia', dialCode: '+966'),
    Country(code: 'RS', name: 'Serbia', dialCode: '+381'),
    Country(code: 'SG', name: 'Singapore', dialCode: '+65'),
    Country(code: 'SK', name: 'Slovakia', dialCode: '+421'),
    Country(code: 'SI', name: 'Slovenia', dialCode: '+386'),
    Country(code: 'ZA', name: 'South Africa', dialCode: '+27'),
    Country(code: 'KR', name: 'South Korea', dialCode: '+82'),
    Country(code: 'ES', name: 'Spain', dialCode: '+34'),
    Country(code: 'LK', name: 'Sri Lanka', dialCode: '+94'),
    Country(code: 'SD', name: 'Sudan', dialCode: '+249'),
    Country(code: 'SE', name: 'Sweden', dialCode: '+46'),
    Country(code: 'CH', name: 'Switzerland', dialCode: '+41'),
    Country(code: 'SY', name: 'Syria', dialCode: '+963'),
    Country(code: 'TW', name: 'Taiwan', dialCode: '+886'),
    Country(code: 'TJ', name: 'Tajikistan', dialCode: '+992'),
    Country(code: 'TH', name: 'Thailand', dialCode: '+66'),
    Country(code: 'TN', name: 'Tunisia', dialCode: '+216'),
    Country(code: 'TR', name: 'Türkiye', dialCode: '+90'),
    Country(code: 'TM', name: 'Turkmenistan', dialCode: '+993'),
    Country(code: 'UA', name: 'Ukraine', dialCode: '+380'),
    Country(code: 'AE', name: 'United Arab Emirates', dialCode: '+971'),
    Country(code: 'GB', name: 'United Kingdom', dialCode: '+44'),
    Country(code: 'US', name: 'United States', dialCode: '+1'),
    Country(code: 'UY', name: 'Uruguay', dialCode: '+598'),
    Country(code: 'UZ', name: 'Uzbekistan', dialCode: '+998'),
    Country(code: 'VE', name: 'Venezuela', dialCode: '+58'),
    Country(code: 'VN', name: 'Vietnam', dialCode: '+84'),
    Country(code: 'YE', name: 'Yemen', dialCode: '+967'),
  ];

  static Country byCode(String code) {
    return all.firstWhere(
          (c) => c.code.toUpperCase() == code.toUpperCase(),
      orElse: () => defaultCountry,
    );
  }
}
