class CompleteSetupPayload {
  final String firstName;
  final String lastName;

  // final String phone;
  final String gender;
  final String dob;
  final double lat;
  final double lng;
  final List<int>? interestIds;

  CompleteSetupPayload({
    required this.firstName,
    required this.lastName,
    // required this.phone,
    required this.gender,
    required this.dob,
    required this.lat,
    required this.lng,
    this.interestIds,
  });

  Map<String, dynamic> toJson() {
    return {
      'first_name': firstName,
      'last_name': lastName,
      // 'phone': phone,
      'gender': gender,
      'dob': dob,
      'lat': lat,
      'lng': lng,
      if (interestIds != null) 'interests': interestIds,
    };
  }
}
