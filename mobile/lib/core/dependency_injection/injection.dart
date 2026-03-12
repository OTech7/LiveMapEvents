import 'package:dio/dio.dart' as dio;
import 'package:get_it/get_it.dart';
import 'package:mobile/core/app_router/app_router.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../network/api_endpoints.dart';
import '../network/interceptor.dart';

final sl = GetIt.instance;

Future<void> init() async {
  ///         Bloc's

  ///         UseCases

  ///     Repositories

  ///     DataSources

  ///     External
  sl.registerLazySingleton(
    () => dio.Dio(
      dio.BaseOptions(
        baseUrl: EndPoints.BASE_URL,
        connectTimeout: const Duration(seconds: 30),
        receiveTimeout: const Duration(seconds: 30),
      ),
    ),
  );

  final sharedPrefs = await SharedPreferences.getInstance();
  sl.registerLazySingleton<SharedPreferences>(() => sharedPrefs);

  ///     Core
  sl.registerLazySingleton(
    () => AppInterceptor(dio: sl(), authInterface: sl(),BASE_URL: EndPoints.BASE_URL,
      loginEndpoint: EndPoints.login,navigatorKey:navigatorKey,),
  );
  sl<dio.Dio>().interceptors.add(sl<AppInterceptor>());
}
