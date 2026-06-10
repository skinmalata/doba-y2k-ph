import 'package:http/http.dart' as http;
import 'dart:convert';
import '../api_config.dart';

class ApiService {
  static Future<Map<String, dynamic>> get(String endpoint,
      {String? token}) async {
    final uri = Uri.parse('$baseUrl/$endpoint');
    final headers = <String, String>{
      'Content-Type': 'application/json',
    };
    if (token != null) headers['Authorization'] = 'Bearer $token';
    final res = await http.get(uri, headers: headers).timeout(const Duration(seconds: 15));
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> post(String endpoint,
      {Map<String, dynamic>? body, String? token}) async {
    final uri = Uri.parse('$baseUrl/$endpoint');
    final headers = <String, String>{
      'Content-Type': 'application/json',
    };
    if (token != null) headers['Authorization'] = 'Bearer $token';
    final res = await http
        .post(uri, headers: headers, body: jsonEncode(body ?? {}))
        .timeout(const Duration(seconds: 15));
    return jsonDecode(res.body);
  }
}
