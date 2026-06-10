import 'package:flutter/material.dart';
import '../services/api_service.dart';
import '../services/auth_service.dart';
import '../models/minute.dart';
import 'minutes/minute_view_screen.dart';

class MinuteListScreen extends StatefulWidget {
  const MinuteListScreen({super.key});

  @override
  State<MinuteListScreen> createState() => _MinuteListScreenState();
}

class _MinuteListScreenState extends State<MinuteListScreen> {
  List<Minute> _minutes = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
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
      }
    } catch (_) {}
    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(title: const Text('Minutes')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
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
