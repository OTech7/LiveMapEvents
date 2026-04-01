class CompleteSetupPayload {
  final String fullName;
  final String? bio;
  final String? profilePhoto;
  final List<int> interestIds;

  CompleteSetupPayload({
    required this.fullName,
    this.bio,
    this.profilePhoto,
    required this.interestIds,
  });

  Map<String, dynamic> toJson() {
    return {
      'full_name': fullName,
      'bio': bio,
      'profile_photo': profilePhoto,
      'interests': interestIds,
    };
  }
}
