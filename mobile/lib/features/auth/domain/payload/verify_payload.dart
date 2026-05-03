class VerifyPayload {
  final String code;

  VerifyPayload({required this.code});

  Map<String, dynamic> toJson() {
    return {
      'code': code,
    };
  }
}
