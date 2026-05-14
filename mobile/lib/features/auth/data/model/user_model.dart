import '../../domain/entity/user_entity.dart';

class UserModel extends UserEntity {
  UserModel({
    required super.id,
    required super.phone,
    required super.firstName,
    required super.lastName,
    required super.dob,
    required super.gender,
    required super.userType,
    super.lat,
    super.lng,
  });

  factory UserModel.fromJson(Map<String, dynamic> json) {
    final location = json['location'] as Map<String, dynamic>?;
    final coordinates = location?['coordinates'] as List<dynamic>?;
    
    return UserModel(
      id: json['id'],
      phone: json['phone'],
      firstName: json['first_name'] ?? '',
      lastName: json['last_name'] ?? '',
      dob: json['dob'] ?? '',
      gender: json['gender'] ?? '',
      userType: json['user_type'] ?? '',
      lat: coordinates != null && coordinates.length > 0 ? coordinates[0].toDouble() : null,
      lng: coordinates != null && coordinates.length > 1 ? coordinates[1].toDouble() : null,
    );
  }
}
