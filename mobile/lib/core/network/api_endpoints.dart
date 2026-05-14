import '../config/env_vars.dart';

class EndPoints {
  static String BASE_URL = EnvVars.baseUrl;
  static const String login = "auth/login";
  static const String sendOTP = "auth/phone/request-otp";
  static const String register = "auth/register";
  static const String logout = "auth/logout";
  static const String verify = "auth/phone/verify-otp";
  static const String interests = "interests";
  static const String assignInterests = "profile/interests";
  static const String completeSetup = "auth/complete-profile";
  static const String googleAuth = "auth/google";
  static const String discoverySettings = "profile/discovery-settings";
}
