import 'package:dartz/dartz.dart';
import '../../../../core/network/api_endpoints.dart';
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
    final response = await interceptor.get(EndPoints.interests, withToken: true);
    return (response.data['data'] as List)
        .map((e) => InterestModel.fromJson(e))
        .toList();
  }

  @override
  Future<Unit> completeSetup(CompleteSetupPayload payload) async {
    await interceptor.post(
      EndPoints.completeSetup,
      body: payload.toJson(),
      withToken: true,
    );
    return unit;
  }
}
