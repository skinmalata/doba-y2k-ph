import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:http_parser/http_parser.dart';

class ApiService {
  static const String _domainUrl = 'https://dobay2k.unaux.com/oldboys/api';
  static const String _ipUrl = 'https://185.27.134.115/oldboys/api';

  // Pre-computed AES-128-CBC cookies (server challenge values are static).
  static const String _domainCookie = '331ae1746b133ada0fc284fff9a9b629';
  static const String _ipCookie = '2f9234f152067b660d3484cd6d5c615f';

  static String _baseUrl = _domainUrl;
  static String? _testCookie;
  static DateTime? _cookieExpiry;

  static String _getCurrentCookie() =>
      _baseUrl == _ipUrl ? _ipCookie : _domainCookie;

  static Future<Map<String, dynamic>> upload(String endpoint,
      {required Map<String, String> fields, File? photo, String? token}) async {
    Future<http.Response> _doUpload() async {
      var req = http.MultipartRequest('POST', Uri.parse('$_baseUrl/$endpoint'));
      req.headers['Cookie'] = '__test=$_testCookie';
      if (token != null) req.headers['Authorization'] = 'Bearer $token';
      req.fields.addAll(fields);
      if (photo != null) {
        req.files.add(await http.MultipartFile.fromPath('photo', photo.path,
            contentType: MediaType('image', photo.path.endsWith('png') ? 'png' : 'jpeg')));
      }
      var streamed = await req.send().timeout(const Duration(seconds: 30));
      return http.Response.fromStream(streamed);
    }

    _ensureCookie();
    try {
      var res = await _doUpload();
      if (_isChallenge(res.body)) { _refreshCookie(); res = await _doUpload(); }
      if (_isChallenge(res.body)) return {'success': false, 'message': 'Server challenge failed. Try again.'};
      return jsonDecode(res.body);
    } on SocketException catch (e) {
      if (_baseUrl == _domainUrl && _isDnsError(e)) {
        _switchToIp();
        var res = await _doUpload();
        if (_isChallenge(res.body)) { _refreshCookie(); res = await _doUpload(); }
        if (_isChallenge(res.body)) return {'success': false, 'message': 'Server challenge failed. Try again.'};
        return jsonDecode(res.body);
      }
      rethrow;
    }
  }

  static Future<Map<String, dynamic>> get(String endpoint, {String? token}) async {
    return _request(
      (headers) => http.get(Uri.parse('$_baseUrl/$endpoint'), headers: headers),
      token: token,
    );
  }

  static Future<Map<String, dynamic>> post(String endpoint,
      {Map<String, dynamic>? body, String? token}) async {
    final encoded = jsonEncode(body ?? {});
    return _request(
      (headers) => http.post(Uri.parse('$_baseUrl/$endpoint'), headers: headers, body: encoded),
      token: token,
      body: encoded,
    );
  }

  static Future<Map<String, dynamic>> _request(
    Future<http.Response> Function(Map<String, String>) send, {
    String? token,
    String? body,
  }) async {
    _ensureCookie();
    try {
      var headers = _buildHeaders(token);
      var res = await send(headers).timeout(const Duration(seconds: 20));
      var responseBody = res.body;

      if (_isChallenge(responseBody)) {
        _refreshCookie();
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
    } on SocketException catch (e) {
      if (_baseUrl == _domainUrl && _isDnsError(e)) {
        _switchToIp();
        return _request(send, token: token, body: body);
      }
      rethrow;
    }
  }

  static void _ensureCookie() {
    if (_testCookie == null || (_cookieExpiry != null && DateTime.now().isAfter(_cookieExpiry!))) {
      _testCookie = _getCurrentCookie();
      _cookieExpiry = DateTime.now().add(const Duration(hours: 6));
    }
  }

  static void _refreshCookie() {
    _testCookie = _getCurrentCookie();
    _cookieExpiry = DateTime.now().add(const Duration(hours: 6));
  }

  static void _switchToIp() {
    _baseUrl = _ipUrl;
    _testCookie = _ipCookie;
    _cookieExpiry = DateTime.now().add(const Duration(hours: 6));
  }

  static bool _isDnsError(SocketException e) {
    final msg = (e.message ?? '').toLowerCase();
    return msg.contains('host lookup') || msg.contains('resolve') || msg.contains('dns');
  }

  static Map<String, String> _buildHeaders(String? token) {
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'User-Agent': 'DobaY2kApp/1.0 (Android)',
    };
    if (_baseUrl == _ipUrl) headers['Host'] = 'dobay2k.unaux.com';
    if (token != null) headers['Authorization'] = 'Bearer $token';
    if (_testCookie != null) headers['Cookie'] = '__test=$_testCookie';
    return headers;
  }

  static bool _isChallenge(String body) => body.contains('__test') && body.contains('slowAES');
}
