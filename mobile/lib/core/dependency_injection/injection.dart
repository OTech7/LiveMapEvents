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
import 'package:mobile/features/profile/data/data_source/profile_remote_data_source.dart';
import 'package:mobile/features/profile/domain/repository/profile_repository.dart';
import 'package:mobile/features/profile/presentation/bloc/profile_bloc.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../features/auth/data/data_source/auth_remote_data_source.dart';
import '../../features/auth/data/repository/auth_repository_impl.dart';
import '../../features/auth/domain/repository/auth_repository.dart';
import '../../features/profile/data/repository/profile_repository_impl.dart';
import '../../features/profile/domain/use_case/complete_setup_usecase.dart';
import '../../features/profile/domain/use_case/get_interests_usecase.dart';
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
  sl.registerFactory(
    () => ProfileBloc(completeSetupUseCase: sl(), getInterestsUseCase: sl()),
  );

  ///         UseCases
  sl.registerLazySingleton(() => LoginUseCase(repository: sl()));
  sl.registerLazySingleton(() => RegisterUseCase(repository: sl()));
  sl.registerLazySingleton(() => LogoutUseCase(repository: sl()));
  sl.registerLazySingleton(() => CheckTokenUseCase(repository: sl()));
  sl.registerLazySingleton(() => VerifyUseCase(repository: sl()));
  sl.registerLazySingleton(() => GetInterestsUseCase(sl()));
  sl.registerLazySingleton(() => CompleteSetupUseCase(sl()));

  ///     Repositories

  sl.registerLazySingleton<AuthRepository>(
    () => AuthRepositoryImpl(localDataSource: sl(), remoteDataSource: sl()),
  );
  sl.registerLazySingleton<ProfileRepository>(
    () => ProfileRepositoryImpl(remoteDataSource: sl()),
  );

  ///     DataSources
  sl.registerLazySingleton<ProfileDataSource>(
    () => ProfileDataSourceImpl(interceptor: sl()),
  );
  sl.registerLazySingleton<AuthDataSource>(
    () => AuthDataSourceImpl(interceptor: sl()),
  );
  sl.registerLazySingleton<AuthLocalDataSource>(
    () => AuthLocalDataSourceImpl(sharedPreferences: sl()),
  );

  sl.registerLazySingleton<AuthTokenProvider>(
    () => AuthLocalDataSourceImpl(sharedPreferences: sl()),
  );

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
