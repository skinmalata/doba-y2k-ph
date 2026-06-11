import 'package:flutter/material.dart';
import '../services/auth_service.dart';
import '../services/api_service.dart';
import '../models/minute.dart';
import '../models/member.dart';
import 'members/edit_profile_screen.dart';
import 'login_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _memberCount = 0;
  int _minutesCount = 0;
  List<Minute> _latestMinutes = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() { _loading = true; _error = null; });
    try {
      final token = await AuthService.getToken();
      final res = await ApiService.get('index.php', token: token);
      if (res['success'] == true) {
        setState(() {
          _memberCount = res['member_count'] ?? 0;
          _minutesCount = res['minutes_count'] ?? 0;
          _latestMinutes = (res['latest_minutes'] as List? ?? [])
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

  Future<void> _logout() async {
    await AuthService.logout();
    if (!mounted) return;
    Navigator.pushReplacementNamed(context, '/login');
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(title: const Text('DOBA Y2k')),
      drawer: _buildDrawer(),
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
                        onPressed: _loadData,
                        icon: const Icon(Icons.refresh),
                        label: const Text('Retry'),
                      ),
                    ],
                  ),
                )
              : RefreshIndicator(
              onRefresh: _loadData,
              child: SingleChildScrollView(
                physics: const AlwaysScrollableScrollPhysics(),
                child: Column(
                  children: [
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.symmetric(vertical: 32),
                      child: Column(
                        children: [
                          Image.asset('assets/splash.jpg',
                              width: 100, height: 100),
                          const SizedBox(height: 12),
                          const Text('DOBA Millenium Set',
                              style: TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF1a1a2e))),
                          const Text('(Port Harcourt Branch)',
                              style: TextStyle(
                                  fontSize: 14, color: Color(0xFFd4af37))),
                        ],
                      ),
                    ),
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: 16),
                      child: Row(
                        children: [
                          _StatCard(
                              icon: Icons.people,
                              label: 'Members',
                              value: _memberCount.toString(),
                              color: const Color(0xFF0d6efd)),
                          const SizedBox(width: 8),
                          _StatCard(
                              icon: Icons.description,
                              label: 'Minutes',
                              value: _minutesCount.toString(),
                              color: const Color(0xFF198754)),
                        ],
                      ),
                    ),
                    if (_latestMinutes.isNotEmpty) ...[
                      const Padding(
                        padding:
                            EdgeInsets.fromLTRB(16, 24, 16, 8),
                        child: Align(
                          alignment: Alignment.centerLeft,
                          child: Text('Latest Minutes',
                              style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.bold)),
                        ),
                      ),
                      ..._latestMinutes.map((m) => ListTile(
                            title: Text(m.title,
                                maxLines: 1, overflow: TextOverflow.ellipsis),
                            subtitle: Text(
                                '${m.meetingDate} · ${m.authorName ?? ''}'),
                            trailing: const Icon(Icons.chevron_right),
                            onTap: () => Navigator.pushNamed(
                                context, '/minutes/view',
                                arguments: m.id),
                          )),
                    ],
                    const SizedBox(height: 24),
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildDrawer() {
    return Drawer(
      child: ListView(
        padding: EdgeInsets.zero,
        children: [
          DrawerHeader(
            decoration: const BoxDecoration(color: Color(0xFF1a1a2e)),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                Image.asset('assets/splash.jpg',
                    width: 50, height: 50),
                const SizedBox(height: 8),
                FutureBuilder<String?>(
                  future: AuthService.getMemberName(),
                  builder: (_, snap) => Text(
                    snap.data ?? 'Member',
                    style: const TextStyle(
                        color: Colors.white, fontSize: 18),
                  ),
                ),
              ],
            ),
          ),
          _drawerItem(Icons.home, 'Home', () {
            Navigator.pop(context);
          }),
          _drawerItem(Icons.people, 'Members', () {
            Navigator.pop(context);
            Navigator.pushNamed(context, '/members');
          }),
          _drawerItem(Icons.person, 'Edit Profile', () async {
            Navigator.pop(context);
            final token = await AuthService.getToken();
            if (token == null) return;
            final res = await ApiService.get('members/profile.php?id=${await AuthService.getMemberId()}', token: token);
            if (res['success'] == true) {
              final member = Member.fromJson(res['member']);
              if (!context.mounted) return;
              await Navigator.push(context,
                  MaterialPageRoute(builder: (_) => EditProfileScreen(member: member)));
            }
          }),
          _drawerItem(Icons.description, 'Minutes', () {
            Navigator.pop(context);
            Navigator.pushNamed(context, '/minutes');
          }),
          _drawerItem(Icons.account_balance, 'Levies', () {
            Navigator.pop(context);
            Navigator.pushNamed(context, '/levies');
          }),
          _drawerItem(Icons.wallet, 'Accounts', () {
            Navigator.pop(context);
            Navigator.pushNamed(context, '/accounts');
          }),
          FutureBuilder<bool>(
            future: AuthService.isAdmin(),
            builder: (_, snap) {
              if (snap.data != true) return const SizedBox.shrink();
              return Column(
                children: [
                  const Divider(),
                  _drawerItem(Icons.admin_panel_settings, 'Admin — Members',
                      () {
                    Navigator.pop(context);
                    Navigator.pushNamed(context, '/admin/members');
                  }),
                ],
              );
            },
          ),
          const Divider(),
          _drawerItem(Icons.logout, 'Logout', () async {
            Navigator.pop(context);
            await _logout();
          }),
        ],
      ),
    );
  }

  Widget _drawerItem(IconData icon, String label, VoidCallback onTap) {
    return ListTile(
      leading: Icon(icon, color: const Color(0xFF1a1a2e)),
      title: Text(label),
      onTap: onTap,
    );
  }
}

class _StatCard extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final Color color;

  const _StatCard(
      {required this.icon,
      required this.label,
      required this.value,
      required this.color});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            children: [
              Icon(icon, size: 36, color: color),
              const SizedBox(height: 8),
              Text(value,
                  style: TextStyle(
                      fontSize: 28,
                      fontWeight: FontWeight.bold,
                      color: color)),
              Text(label, style: const TextStyle(color: Colors.grey)),
            ],
          ),
        ),
      ),
    );
  }
}
