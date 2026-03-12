import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/cupertino.dart';
import 'package:go_router/go_router.dart';
import 'package:logger/logger.dart';
import 'package:mobile/core/network/token_provider.dart';

import '../error_handling/exceptions.dart';

class AppInterceptor extends Interceptor {
  final AuthTokenProvider authInterface;

  final Dio dio;
  Dio refreshDio;
  String loginEndpoint;
  String BASE_URL;
  GlobalKey<NavigatorState> navigatorKey;

  AppInterceptor({
    required this.dio,
    required this.authInterface,
    required this.loginEndpoint,
    required this.BASE_URL,
    required this.navigatorKey,
    Dio? refreshDio,
  }) : refreshDio = refreshDio ?? Dio();

  /// ---------------- REQUEST ----------------
  @override
  void onRequest(
      RequestOptions options,
      RequestInterceptorHandler handler,
      ) async {
    if (options.extra['requiresToken'] == true) {
      final token = await authInterface.getToken();
      if (token != null) {
        options.headers['Authorization'] = 'Bearer $token';
      }
    }
    options.extra.remove('requiresToken');

    logger.d("➡️ ${options.method} ${options.uri}");
    handler.next(options);
  }

  /// ---------------- RESPONSE ----------------
  @override
  void onResponse(Response response, ResponseInterceptorHandler handler) {
    response.logResponse();
    handler.next(response);
  }

  /// ---------------- ERROR ----------------
  @override
  void onError(DioException err, ErrorInterceptorHandler handler) async {
    final status = err.response?.statusCode;
    err.logError();
    if (status == 401 && !err.requestOptions.path.contains(loginEndpoint)) {
      _forceLogout();
      return handler.next(err);
    }

    final mappedError = _mapDioErrorToException(err);
    handler.next(err.copyWith(error: mappedError));
  }

  /// ---------------- ERROR MAPPING ----------------
  void _forceLogout() async {
    await authInterface.deleteUser();

    final context = navigatorKey.currentContext;
    context?.go("/login");
  }

  Exception _mapDioErrorToException(DioException err) {
    final status = err.response?.statusCode;

    switch (status) {
      case 401:
        if (err.requestOptions.path.contains(loginEndpoint)) {
          return WrongDataException(message: err.response?.data?['message']);
        }
        return UnAuthorizedException();

      case 403:
        return BlockedException();
      case 404:
        return NotFoundException();
      case 422:
        return WrongDataException(message: err.response?.data?['message']);
      default:
        return ServerException();
    }
  }

  /// ---------------- HEADERS ----------------
  Future<Map<String, String>> getHeaders() async {
    final headers = {
      "Content-Type": "application/json",
      "Accept": "application/json",
    };
    return headers;
  }

  /// ---------------- HELPER METHODS ----------------

  Future<Response> get(
      String url, {
        Map<String, dynamic>? query,
        bool withToken = false,
      }) async {
    final headers = await getHeaders();
    final response = await dio.get(
      BASE_URL + url,
      queryParameters: query,
      options: Options(headers: headers, extra: {'requiresToken': withToken}),
    );
    return response;
  }

  Future<Response> post(
      String url, {
        Map<String, dynamic>? body,
        bool withToken = false,
      }) async {
    final headers = await getHeaders();
    final response = await dio.post(
      BASE_URL + url,
      data: body != null ? jsonEncode(body) : null,
      options: Options(headers: headers, extra: {'requiresToken': withToken}),
    );
    return response;
  }

  Future<Response> put(
      String url, {
        Map<String, dynamic>? body,
        bool withToken = false,
      }) async {
    final headers = await getHeaders();
    final response = await dio.put(
      BASE_URL + url,
      data: body != null ? jsonEncode(body) : null,
      options: Options(headers: headers, extra: {'requiresToken': withToken}),
    );
    return response;
  }

  Future<Response> delete(String url, {bool withToken = false}) async {
    final headers = await getHeaders();
    final response = await dio.delete(
      BASE_URL + url,
      options: Options(headers: headers, extra: {'requiresToken': withToken}),
    );
    return response;
  }
}

/// ---------------- LOGGING EXTENSION ----------------
bool withLog = true;

final logger = Logger(
  printer: PrettyPrinter(
    methodCount: 0,
    errorMethodCount: 0,
    lineLength: 100,
    colors: true,
    printEmojis: true,
    printTime: false,
  ),
);

String _formatLog({
  required Uri uri,
  required String method,
  required Map<String, dynamic> headers,
  dynamic body,
  int? status,
  dynamic response,
  dynamic error,
  required bool isError,
}) {
  final title = isError ? 'Dio Error' : 'Response';

  return "\n--- $title ---\n"
      "URL: $uri\n"
      "METHOD: $method\n"
      "HEADERS: $headers\n"
      "BODY: $body\n"
      "STATUS: $status\n"
      "RESPONSE: $response\n"
      "${error != null ? 'ERROR: $error\n' : ''}"
      "----------------\n";
}

extension ResponseLogger on Response<dynamic> {
  void logResponse() {
    if (!withLog) return;

    final level = statusCode != null && statusCode! < 400
        ? logger.i
        : statusCode != null && statusCode! < 500
        ? logger.w
        : logger.e;

    level(
      _formatLog(
        uri: requestOptions.uri,
        method: requestOptions.method,
        headers: requestOptions.headers,
        body: requestOptions.data,
        status: statusCode,
        response: data,
        isError: false,
      ),
    );
  }
}

extension DioErrorLogger on DioException {
  void logError() {
    if (!withLog) return;

    logger.e(
      _formatLog(
        uri: requestOptions.uri,
        method: requestOptions.method,
        headers: requestOptions.headers,
        body: requestOptions.data,
        status: response?.statusCode,
        response: response?.data,
        error: error,
        isError: true,
      ),
    );
  }
}
