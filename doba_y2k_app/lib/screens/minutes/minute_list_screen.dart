import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../models/minute.dart';
import 'minute_view_screen.dart';

class MinuteListScreen extends StatefulWidget {
  const MinuteListScreen({super.key});

  @override
  State<MinuteListScreen> createState() => _MinuteListScreenState();
}

class _MinuteListScreenState extends State<MinuteListScreen> {
  List<Minute> _minutes = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() { _loading = true; _error = null; });
    try {
      final token = await AuthService.getToken();
      final res = await ApiService.get('minutes/index.php', token: token);
      if (res['success'] == true) {
        setState(() {
          _minutes = (res['minutes'] as List)
              .map((j) => Minute.fromJson(j))
              .toList();
          _loading = false;
        });
      } else {
        setState(() { _error = res['message'] ?? 'Server error'; _loading = false; });
      }
    } catch (e) {
      setState(() { _error = 'Connection error. Pull down to retry.'; _loading = false; });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(title: const Text('Minutes')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _error != null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.cloud_off, size: 64, color: Colors.grey),
                      const SizedBox(height: 16),
                      Text(_error!, textAlign: TextAlign.center,
                          style: const TextStyle(fontSize: 16, color: Colors.grey)),
                      const SizedBox(height: 24),
                      FilledButton.tonalIcon(
                        onPressed: _load,
                        icon: const Icon(Icons.refresh),
                        label: const Text('Retry'),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
              onRefresh: _load,
              child: _minutes.isEmpty
                  ? const Center(child: Text('No minutes yet'))
                  : ListView.builder(
                      itemCount: _minutes.length,
                      itemBuilder: (_, i) {
                        final m = _minutes[i];
                        return Card(
                          margin: const EdgeInsets.symmetric(
                              horizontal: 12, vertical: 4),
                          child: ListTile(
                            title: Text(m.title,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis),
                            subtitle: Text(
                                '${m.meetingDate} · ${m.authorName ?? ''}'),
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () => Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) =>
                                    MinuteViewScreen(minuteId: m.id),
                              ),
                            ),
                          ),
                        );
                      },
                    ),
            ),
    );
  }
}
