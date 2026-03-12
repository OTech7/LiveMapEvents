class RegisterPayload {
  // final String countryCode;
  // final String phoneNumber;
  final String email;
  final String password;
  final String firstName;
  final String lastName;

  // final String address;

  RegisterPayload({
    // required this.countryCode,
    // required this.phoneNumber,
    required this.email,
    required this.password,
    required this.firstName,
    required this.lastName,
    // required this.address,
  });

  Map<String, dynamic> toJson() => {
        // "countryCode": countryCode,
        // "phoneNumber": phoneNumber,
        "email": email,
        "password": password,
        "firstname": firstName,
        "lastname": lastName,
      };
}
