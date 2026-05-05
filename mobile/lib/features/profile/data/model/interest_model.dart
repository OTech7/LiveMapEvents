import '../../domain/entity/interest_entity.dart';

class InterestModel extends InterestEntity {
  InterestModel({required super.id, required super.name, required super.icon});

  factory InterestModel.fromJson(Map<String, dynamic> json) {
    return InterestModel(
      id: json['id'],
      name: json['name'],
      icon: json['icon'],
    );
  }

  Map<String, dynamic> toJson() {
    return {'id': id, 'name': name, 'icon': icon};
  }
}
