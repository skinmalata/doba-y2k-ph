import 'package:shared_preferences/shared_preferences.dart';

class AuthService {
  static const _tokenKey = 'auth_token';
  static const _memberIdKey = 'member_id';
  static const _memberNameKey = 'member_name';
  static const _roleKey = 'role';

  static Future<void> saveSession({
    required String token,
    required int memberId,
    required String memberName,
    required String role,
  }) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setInt(_memberIdKey, memberId);
    await prefs.setString(_memberNameKey, memberName);
    await prefs.setString(_roleKey, role);
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  static Future<int?> getMemberId() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getInt(_memberIdKey);
  }

  static Future<String?> getMemberName() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_memberNameKey);
  }

  static Future<String?> getRole() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_roleKey);
  }

  static Future<bool> isLoggedIn() async {
    final token = await getToken();
    return token != null && token.isNotEmpty;
  }

  static Future<bool> isAdmin() async {
    final role = await getRole();
    return role == 'admin';
  }

  static Future<void> logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_memberIdKey);
    await prefs.remove(_memberNameKey);
    await prefs.remove(_roleKey);
  }
}
