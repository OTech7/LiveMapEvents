enum Gender { male, female }

class GenderValues {
  static final genderValues = EnumValues({
    "male": Gender.male,
    "female": Gender.female,
  });
}

enum Language { ar, en }

class LanguageValues {
  static final languageValues = EnumValues({
    "ar": Language.ar,
    "en": Language.en,
  });
}

class EnumValues<T> {
  Map<String, T> map;
  late Map<T, String> reverseMap;

  EnumValues(this.map);

  Map<T, String> get reverse {
    reverseMap = map.map((k, v) => MapEntry(v, k));
    return reverseMap;
  }
}
