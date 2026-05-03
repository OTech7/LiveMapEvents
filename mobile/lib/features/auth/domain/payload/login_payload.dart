class LoginPayload {
  // final String countryCode;
  // final String phoneNumber;
  final String email;
  final String password;

  LoginPayload({
    // required this.countryCode,
    // required this.phoneNumber,
    required this.email,
    required this.password,
  });

  Map<String, dynamic> toJson() => {
        // "countryCode": countryCode,
        // "phoneNumber": phoneNumber,
        "email": email,
        "password": password,
      };
}
