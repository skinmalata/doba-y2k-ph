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

    if (_isChallenge(res.body)) {
      final cookie = _solveChallenge(res.body);
      _testCookie = cookie;
      _cookieExpiry = DateTime.now().add(const Duration(hours: 6));
      headers = _buildHeaders(token);
      final redirectUrl = _parseRedirectUrl(res.body);
      final uri = redirectUrl != null ? Uri.parse(redirectUrl) : res.request!.url;
      res = body != null
          ? await http.post(uri, headers: headers, body: body).timeout(const Duration(seconds: 20))
          : await http.get(uri, headers: headers).timeout(const Duration(seconds: 20));
    }

    return jsonDecode(res.body);
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
    final url = match.group(1)!;
    return url.startsWith('?') ? '$baseUrl$url' : url;
  }

  static String _solveChallenge(String html) {
    final aReg = RegExp(r'a=toNumbers\("([a-f0-9]+)"\)');
    final bReg = RegExp(r'b=toNumbers\("([a-f0-9]+)"\)');
    final cReg = RegExp(r'c=toNumbers\("([a-f0-9]+)"\)');
    final key = _hexToBytes(aReg.firstMatch(html)!.group(1)!);
    final iv = _hexToBytes(bReg.firstMatch(html)!.group(1)!);
    final ct = _hexToBytes(cReg.firstMatch(html)!.group(1)!);

    // AES-128-CFB decryption: Encrypt IV with key, XOR with ciphertext
    final aes = AESEngine()..init(true, KeyParameter(key));
    final keystream = Uint8List(16);
    aes.processBlock(iv, 0, keystream, 0);
    final plaintext = Uint8List(16);
    for (var i = 0; i < 16; i++) {
      plaintext[i] = keystream[i] ^ ct[i];
    }
    return _bytesToHex(plaintext);
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
