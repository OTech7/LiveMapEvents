class UserEntity {
  final int id;
  final String phone;
  final String firstName;
  final String lastName;
  final String dob;
  final String gender;
  final String userType;
  final double? lat;
  final double? lng;

  UserEntity({
    required this.id,
    required this.phone,
    required this.firstName,
    required this.lastName,
    required this.dob,
    required this.gender,
    required this.userType,
    this.lat,
    this.lng,
  });
}
