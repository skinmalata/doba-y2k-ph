import 'dart:convert';
import 'dart:typed_data';
import 'package:http/http.dart' as http;
import 'package:pointycastle/export.dart';
import '../api_config.dart';

class ApiService {
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
      await _fetchCookie();
    }

    var headers = _buildHeaders(token);
    var res = await send(headers).timeout(const Duration(seconds: 20));
    var responseBody = res.body;

    if (_isChallenge(responseBody)) {
      final cookie = _computeCookie(responseBody);
      if (cookie != null) {
        _testCookie = cookie;
        _cookieExpiry = DateTime.now().add(const Duration(hours: 6));
        headers = _buildHeaders(token);
        res = body != null
            ? await http.post(res.request!.url, headers: headers, body: body).timeout(const Duration(seconds: 20))
            : await http.get(res.request!.url, headers: headers).timeout(const Duration(seconds: 20));
        responseBody = res.body;
      }
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

  static Future<void> _fetchCookie() async {
    try {
      final uri = Uri.parse('$baseUrl/index.php');
      final res = await http.get(uri).timeout(const Duration(seconds: 15));
      if (_isChallenge(res.body)) {
        final cookie = _computeCookie(res.body);
        if (cookie != null) _testCookie = cookie;
        _cookieExpiry = DateTime.now().add(const Duration(hours: 6));
      }
    } catch (_) {}
  }

  static bool _isChallenge(String body) => body.contains('__test') && body.contains('slowAES');

  static String? _computeCookie(String html) {
    final aReg = RegExp(r'a=toNumbers\("([a-f0-9]+)"\)');
    final bReg = RegExp(r'b=toNumbers\("([a-f0-9]+)"\)');
    final cReg = RegExp(r'c=toNumbers\("([a-f0-9]+)"\)');
    final aMatch = aReg.firstMatch(html);
    final bMatch = bReg.firstMatch(html);
    final cMatch = cReg.firstMatch(html);
    if (aMatch == null || bMatch == null || cMatch == null) return null;

    final key = _hexToBytes(aMatch.group(1)!);
    final iv = _hexToBytes(bMatch.group(1)!);
    final ct = _hexToBytes(cMatch.group(1)!);

    try {
      final cipher = CBCBlockCipher(AESEngine())
        ..init(false, ParametersWithIV(KeyParameter(key), iv));
      final pt = Uint8List(16);
      cipher.processBlock(ct, 0, pt, 0);
      return _bytesToHex(pt);
    } catch (_) {
      return null;
    }
  }

  static Uint8List _hexToBytes(String hex) {
    final bytes = Uint8List(hex.length ~/ 2);
    for (var i = 0; i < bytes.length; i++) {
      bytes[i] = int.parse(hex.substring(i * 2, i * 2 + 2), radix: 16);
    }
    return bytes;
  }

  static String _bytesToHex(Uint8List bytes) {
    final buf = StringBuffer();
    for (final b in bytes) { buf.write(b.toRadixString(16).padLeft(2, '0')); }
    return buf.toString();
  }
}
