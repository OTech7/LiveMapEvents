class VerifyPayload {
  final String code;
  final String phoneNumber;

  VerifyPayload({required this.code, required this.phoneNumber});

  Map<String, dynamic> toJson() {
    return {'otp': code, 'phone': phoneNumber};
  }
}
