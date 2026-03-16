class RegisterPayload {
  final String formatCode;
  final String phoneNumber;
  final String email;
  final String password;
  final String firstName;
  final String lastName;

  RegisterPayload({
    required this.formatCode,
    required this.phoneNumber,
    required this.email,
    required this.password,
    required this.firstName,
    required this.lastName,
  });

  Map<String, dynamic> toJson() => {
        "format_code": formatCode,
        "phone_number": phoneNumber,
        "email": email,
        "password": password,
        "first_name": firstName,
        "last_name": lastName,
      };
}
