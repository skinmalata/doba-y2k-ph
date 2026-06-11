import 'dart:convert';
import 'dart:typed_data';
import 'package:http/http.dart' as http;
import '../api_config.dart';

class ApiService {
  // Server challenge values are static — we hardcode the computed cookie.
  // Fallback AES-128-CBC computation is available if they ever change.
  static const String _hardcodedCookie = '331ae1746b133ada0fc284fff9a9b629';
  static String? _testCookie;
  static DateTime? _cookieExpiry;

  static Future<Map<String, dynamic>> get(String endpoint, {String? token}) async {
    return _request(
      (headers) => http.get(Uri.parse('$baseUrl/$endpoint'), headers: headers),
      token: token,
    );
  }

  static Future<Map<String, dynamic>> post(String endpoint,
      {Map<String, dynamic>? body, String? token}) async {
    return _request(
      (headers) => http.post(Uri.parse('$baseUrl/$endpoint'), headers: headers, body: jsonEncode(body ?? {})),
      token: token,
      body: jsonEncode(body ?? {}),
    );
  }

  static Future<Map<String, dynamic>> _request(
    Future<http.Response> Function(Map<String, String>) send, {
    String? token,
    String? body,
  }) async {
    if (_testCookie == null || (_cookieExpiry != null && DateTime.now().isAfter(_cookieExpiry!))) {
      _testCookie = _hardcodedCookie;
      _cookieExpiry = DateTime.now().add(const Duration(hours: 6));
    }

    var headers = _buildHeaders(token);
    var res = await send(headers).timeout(const Duration(seconds: 20));
    var responseBody = res.body;

    if (_isChallenge(responseBody)) {
      _testCookie = _hardcodedCookie;
      _cookieExpiry = DateTime.now().add(const Duration(hours: 6));
      headers = _buildHeaders(token);
      res = body != null
          ? await http.post(res.request!.url, headers: headers, body: body).timeout(const Duration(seconds: 20))
          : await http.get(res.request!.url, headers: headers).timeout(const Duration(seconds: 20));
      responseBody = res.body;
    }

    if (_isChallenge(responseBody)) {
      return {'success': false, 'message': 'Server challenge failed. Try again.'};
    }

    return jsonDecode(responseBody);
  }

  static Map<String, String> _buildHeaders(String? token) {
    final headers = <String, String>{'Content-Type': 'application/json'};
    if (token != null) headers['Authorization'] = 'Bearer $token';
    if (_testCookie != null) headers['Cookie'] = '__test=$_testCookie';
    return headers;
  }

  static bool _isChallenge(String body) => body.contains('__test') && body.contains('slowAES');
}
