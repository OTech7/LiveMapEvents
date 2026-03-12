class RegexPatterns {
  static final RegExp utcIso8601 =
      RegExp(r'^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?Z$');

  static final RegExp anyNonDigits = RegExp(r'\D');

  static final RegExp yyyyMMdd = RegExp(r'^\d{4}-\d{2}-\d{2}$');

  static final RegExp whitespace = RegExp(r'\s+');

  static final RegExp email =
      RegExp(r'^[\w-]+(\.[\w-]+)*@([\w-]+\.)+[a-zA-Z]{2,7}$');

  static final RegExp passportNumber = RegExp(r'^[A-ZA-z0-9]{5,10}$');

  static final RegExp phoneNumber = RegExp(r'^\d{7,15}$');
}
