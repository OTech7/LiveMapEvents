import 'package:dio/dio.dart' as dio;
import 'package:get_it/get_it.dart';
import 'package:mobile/core/app_router/app_router.dart';
import 'package:mobile/features/auth/data/data_source/auth_local_data_source.dart';
import 'package:mobile/features/auth/domain/use_case/checkTokenUseCase.dart';
import 'package:mobile/features/auth/domain/use_case/login_usecase.dart';
import 'package:mobile/features/auth/domain/use_case/logout_usecase.dart';
import 'package:mobile/features/auth/domain/use_case/register_usecase.dart';
import 'package:mobile/features/auth/domain/use_case/verify_usecase.dart';
import 'package:mobile/features/auth/presentation/bloc/auth_bloc.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../features/auth/data/data_source/auth_remote_data_source.dart';
import '../../features/auth/data/repository/auth_repository_impl.dart';
import '../../features/auth/domain/repository/auth_repository.dart';
import '../network/api_endpoints.dart';
import '../network/interceptor.dart';
import '../network/token_provider.dart';

final sl = GetIt.instance;

Future<void> init() async {
  ///         Bloc's
  sl.registerFactory(
    () => AuthBloc(
      loginUseCase: sl(),
      registerUseCase: sl(),
      checkTokenUseCase: sl(),
      logoutUseCase: sl(),
      verifyUseCase: sl(),
    ),
  );

  ///         UseCases
  sl.registerLazySingleton(() => LoginUseCase(repository: sl()));
  sl.registerLazySingleton(() => RegisterUseCase(repository: sl()));
  sl.registerLazySingleton(() => LogoutUseCase(repository: sl()));
  sl.registerLazySingleton(() => CheckTokenUseCase(repository: sl()));
  sl.registerLazySingleton(() => VerifyUseCase(repository: sl()));

  ///     Repositories

  sl.registerLazySingleton<AuthRepository>(
    () => AuthRepositoryImpl(localDataSource: sl(), remoteDataSource: sl()),
  );


  ///     DataSources
  sl.registerLazySingleton<AuthDataSource>(
    () => AuthDataSourceImpl(interceptor: sl()),
  );
  sl.registerLazySingleton<AuthLocalDataSource>(
    () => AuthLocalDataSourceImpl(sharedPreferences: sl()),
  );

  sl.registerLazySingleton<AuthTokenProvider>(
          () => AuthLocalDataSourceImpl(sharedPreferences: sl()));

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
    () => AppInterceptor(
      dio: sl(),
      authInterface: sl(),
      BASE_URL: EndPoints.BASE_URL,
      loginEndpoint: EndPoints.login,
      navigatorKey: navigatorKey,
    ),
  );
  sl<dio.Dio>().interceptors.add(sl<AppInterceptor>());
}
