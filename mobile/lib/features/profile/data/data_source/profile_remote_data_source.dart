import 'package:dartz/dartz.dart';
import '../../../../core/network/interceptor.dart';
import '../../domain/payload/complete_setup_payload.dart';
import '../model/interest_model.dart';

abstract class ProfileDataSource {
  Future<List<InterestModel>> getInterests();

  Future<Unit> completeSetup(CompleteSetupPayload payload);
}

class ProfileDataSourceImpl implements ProfileDataSource {
  final AppInterceptor interceptor;

  ProfileDataSourceImpl({required this.interceptor});

  @override
  Future<List<InterestModel>> getInterests() async {
    await Future.delayed(const Duration(seconds: 1));
    return [
      InterestModel(id: 1, name: 'Music', icon: 'music'),
      InterestModel(id: 2, name: 'Tech', icon: 'tech'),
      InterestModel(id: 3, name: 'Art', icon: 'art'),
      InterestModel(id: 4, name: 'Sports', icon: 'sports'),
      InterestModel(id: 5, name: 'Food', icon: 'food'),
      InterestModel(id: 6, name: 'Networking', icon: 'networking'),
      InterestModel(id: 7, name: 'Wellness', icon: 'wellness'),
      InterestModel(id: 8, name: 'Travel', icon: 'travel'),
      InterestModel(id: 9, name: 'Gaming', icon: 'gaming'),
      InterestModel(id: 10, name: 'Fashion', icon: 'fashion'),
      InterestModel(id: 11, name: 'Business', icon: 'business'),
      InterestModel(id: 12, name: 'Film', icon: 'film'),
    ];
  }

  @override
  Future<Unit> completeSetup(CompleteSetupPayload payload) async {
    await Future.delayed(const Duration(seconds: 1));
    return unit;
  }
}
