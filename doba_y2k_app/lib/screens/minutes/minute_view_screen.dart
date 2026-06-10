import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../models/minute.dart';

class MinuteViewScreen extends StatefulWidget {
  final int minuteId;
  const MinuteViewScreen({super.key, required this.minuteId});

  @override
  State<MinuteViewScreen> createState() => _MinuteViewScreenState();
}

class _MinuteViewScreenState extends State<MinuteViewScreen> {
  Minute? _minute;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final token = await AuthService.getToken();
      final res = await ApiService.get(
          'minutes/view.php?id=${widget.minuteId}',
          token: token);
      if (res['success'] == true) {
        setState(() {
          _minute = Minute.fromJson(res['minute']);
          _loading = false;
        });
      }
    } catch (_) {}
    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        title: Text(_minute?.title ?? 'Minute',
            maxLines: 1, overflow: TextOverflow.ellipsis),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _minute == null
              ? const Center(child: Text('Not found'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(_minute!.title,
                          style: const TextStyle(
                              fontSize: 22,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF1a1a2e))),
                      const SizedBox(height: 8),
                      Text(
                          'Date: ${_minute!.meetingDate} · By: ${_minute!.authorName ?? 'Unknown'}',
                          style:
                              const TextStyle(color: Colors.grey)),
                      const Divider(height: 24),
                      Text(_minute!.content,
                          style: const TextStyle(
                              fontSize: 15, height: 1.6)),
                    ],
                  ),
                ),
    );
  }
}
