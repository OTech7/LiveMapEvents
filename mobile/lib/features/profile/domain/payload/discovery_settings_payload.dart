class DiscoverySettingsPayload {
  final int radius;
  final bool notify;

  DiscoverySettingsPayload({
    required this.radius,
    required this.notify,
  });

  Map<String, dynamic> toJson() {
    return {
      'radius': radius,
      'notifications': notify,
    };
  }
}
