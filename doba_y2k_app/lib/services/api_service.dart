import 'dart:convert';
import 'dart:typed_data';
import 'package:http/http.dart' as http;
import 'package:pointycastle/export.dart';
import '../api_config.dart';

class ApiService {
  static String? _testCookie;
  static DateTime? _cookieExpiry;

  static Future<Map<String, dynamic>> get(String endpoint, {String? token}) async {
    final headers = _buildHeaders(token);
    return _request(() => http.get(Uri.parse('$baseUrl/$endpoint'), headers: headers), headers);
  }

  static Future<Map<String, dynamic>> post(String endpoint,
      {Map<String, dynamic>? body, String? token}) async {
    final headers = _buildHeaders(token);
    final encoded = jsonEncode(body ?? {});
    return _request(
      () => http.post(Uri.parse('$baseUrl/$endpoint'), headers: headers, body: encoded),
      headers,
      body: encoded,
    );
  }

  static Map<String, String> _buildHeaders(String? token) {
    final headers = <String, String>{'Content-Type': 'application/json'};
    if (token != null) headers['Authorization'] = 'Bearer $token';
    if (_testCookie != null) headers['Cookie'] = '__test=$_testCookie';
    return headers;
  }

  static Future<Map<String, dynamic>> _request(
    Future<http.Response> Function() request,
    Map<String, String> headers, {
    String? body,
  }) async {
    if (_testCookie == null || (_cookieExpiry != null && DateTime.now().isAfter(_cookieExpiry!))) {
      await _fetchCookie();
    }
    var res = await request().timeout(const Duration(seconds: 20));
    var responseBody = res.body;
    if (_isChallenge(responseBody)) {
      final redirectUrl = _parseRedirectUrl(responseBody);
      final cookie = _solveChallenge(responseBody);
      _testCookie = cookie;
      _cookieExpiry = DateTime.now().add(const Duration(hours: 6));
      headers['Cookie'] = '__test=$cookie';
      final retryUri = redirectUrl != null ? Uri.parse(redirectUrl) : res.request!.url;
      res = body != null
          ? await http.post(retryUri, headers: headers, body: body).timeout(const Duration(seconds: 20))
          : await http.get(retryUri, headers: headers).timeout(const Duration(seconds: 20));
      responseBody = res.body;
    }
    return jsonDecode(responseBody);
  }

  static Future<void> _fetchCookie() async {
    try {
      final uri = Uri.parse('$baseUrl/index.php');
      final res = await http.get(uri).timeout(const Duration(seconds: 15));
      if (_isChallenge(res.body)) {
        _testCookie = _solveChallenge(res.body);
        _cookieExpiry = DateTime.now().add(const Duration(hours: 6));
      }
    } catch (_) {}
  }

  static bool _isChallenge(String body) => body.contains('__test') && body.contains('slowAES');

  static String? _parseRedirectUrl(String html) {
    final reg = RegExp(r'location\.href\s*=\s*"([^"]+)"');
    final match = reg.firstMatch(html);
    if (match == null) return null;
    var url = match.group(1)!;
    if (url.startsWith('?')) url = '$baseUrl$url';
    return url;
  }

  static String _solveChallenge(String html) {
    final aReg = RegExp(r'a=toNumbers\("([a-f0-9]+)"\)');
    final bReg = RegExp(r'b=toNumbers\("([a-f0-9]+)"\)');
    final cReg = RegExp(r'c=toNumbers\("([a-f0-9]+)"\)');
    final key = _hexToBytes(aReg.firstMatch(html)!.group(1)!);
    final iv = _hexToBytes(bReg.firstMatch(html)!.group(1)!);
    final ct = _hexToBytes(cReg.firstMatch(html)!.group(1)!);

    final cipher = CFBBlockCipher(AESEngine(), 16)
      ..init(false, ParametersWithIV(KeyParameter(key), iv));
    final pt = Uint8List(ct.length);
    cipher.processBlock(ct, 0, pt, 0);
    return _bytesToHex(pt);
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
