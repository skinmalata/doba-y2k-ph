import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../models/member.dart';

class AdminMembersScreen extends StatefulWidget {
  const AdminMembersScreen({super.key});

  @override
  State<AdminMembersScreen> createState() => _AdminMembersScreenState();
}

class _AdminMembersScreenState extends State<AdminMembersScreen> {
  List<Member> _members = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final token = await AuthService.getToken();
      final res =
          await ApiService.get('admin/members.php', token: token);
      if (res['success'] == true) {
        setState(() {
          _members = (res['members'] as List)
              .map((j) => Member.fromJson(j))
              .toList();
          _loading = false;
        });
      }
    } catch (_) {}
    setState(() => _loading = false);
  }

  Future<void> _approve(int id) async {
    try {
      final token = await AuthService.getToken();
      final res = await ApiService.post('admin/approve.php',
          token: token, body: {'member_id': id});
      if (res['success'] == true) _load();
    } catch (_) {}
  }

  Future<void> _toggleAdmin(int id, String currentRole) async {
    try {
      final token = await AuthService.getToken();
      final res = await ApiService.post('admin/members.php',
          token: token,
          body: {
            'action': 'toggle_admin',
            'member_id': id,
          });
      if (res['success'] == true) _load();
    } catch (_) {}
  }

  Future<void> _delete(int id, String name) async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Remove Member'),
        content: Text('Remove $name?'),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel')),
          TextButton(
              onPressed: () => Navigator.pop(context, true),
              child:
                  const Text('Remove', style: TextStyle(color: Colors.red))),
        ],
      ),
    );
    if (confirm != true) return;
    try {
      final token = await AuthService.getToken();
      final res = await ApiService.post('admin/members.php',
          token: token,
          body: {
            'action': 'delete',
            'member_id': id,
          });
      if (res['success'] == true) _load();
    } catch (_) {}
  }

  Future<void> _changePassword(int id, String name) async {
    final passCtrl = TextEditingController();
    final confirmCtrl = TextEditingController();
    final result = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: Text('Change Password — $name'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
                controller: passCtrl,
                obscureText: true,
                decoration: const InputDecoration(
                    labelText: 'New Password', border: OutlineInputBorder())),
            const SizedBox(height: 12),
            TextField(
                controller: confirmCtrl,
                obscureText: true,
                decoration: const InputDecoration(
                    labelText: 'Confirm',
                    border: OutlineInputBorder())),
          ],
        ),
        actions: [
          TextButton(
              onPressed: () => Navigator.pop(context, false),
              child: const Text('Cancel')),
          TextButton(
              onPressed: () => Navigator.pop(context, true),
              child: const Text('Change')),
        ],
      ),
    );
    if (result != true ||
        passCtrl.text.isEmpty ||
        passCtrl.text != confirmCtrl.text) return;
    try {
      final token = await AuthService.getToken();
      await ApiService.post('admin/change_password.php',
          token: token,
          body: {
            'member_id': id,
            'password': passCtrl.text,
          });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Password changed')));
    } catch (_) {}
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(title: const Text('Manage Members')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView.builder(
                itemCount: _members.length,
                itemBuilder: (_, i) {
                  final m = _members[i];
                  return Card(
                    margin:
                        const EdgeInsets.symmetric(horizontal: 12, vertical: 3),
                    child: ListTile(
                      leading: CircleAvatar(
                        child: Text(m.firstName.isNotEmpty
                            ? m.firstName[0].toUpperCase()
                            : '?'),
                      ),
                      title: Text(m.fullName),
                      subtitle: Text(m.username),
                      trailing: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          if (m.status == 'pending')
                            IconButton(
                              icon: const Icon(Icons.check_circle,
                                  color: Colors.green),
                              onPressed: () => _approve(m.id),
                              tooltip: 'Approve',
                            ),
                          IconButton(
                            icon: const Icon(Icons.key,
                                color: Colors.orange),
                            onPressed: () =>
                                _changePassword(m.id, m.fullName),
                            tooltip: 'Change Password',
                          ),
                          IconButton(
                            icon: Icon(
                              m.role == 'admin'
                                  ? Icons.admin_panel_settings
                                  : Icons.person,
                              color: m.role == 'admin'
                                  ? Colors.red
                                  : Colors.grey,
                            ),
                            onPressed: () =>
                                _toggleAdmin(m.id, m.role),
                            tooltip: 'Toggle admin',
                          ),
                          IconButton(
                            icon: const Icon(Icons.delete,
                                color: Colors.red),
                            onPressed: m.role == 'admin'
                                ? null
                                : () => _delete(m.id, m.fullName),
                            tooltip: 'Delete',
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ),
    );
  }
}
