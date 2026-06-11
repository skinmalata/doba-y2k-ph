import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../models/member.dart';

class MemberListScreen extends StatefulWidget {
  const MemberListScreen({super.key});

  @override
  State<MemberListScreen> createState() => _MemberListScreenState();
}

class _MemberListScreenState extends State<MemberListScreen> {
  List<Member> _members = [];
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
      final res =
          await ApiService.get('members/index.php', token: token);
      if (res['success'] == true) {
        setState(() {
          _members =
              (res['members'] as List).map((j) => Member.fromJson(j)).toList();
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
      appBar: AppBar(title: const Text('Members')),
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
              child: _members.isEmpty
                  ? const Center(child: Text('No members found'))
                  : GridView.builder(
                      padding: const EdgeInsets.all(12),
                      gridDelegate:
                          const SliverGridDelegateWithFixedCrossAxisCount(
                        crossAxisCount: 2,
                        childAspectRatio: 0.85,
                        crossAxisSpacing: 8,
                        mainAxisSpacing: 8,
                      ),
                      itemCount: _members.length,
                      itemBuilder: (_, i) {
                        final m = _members[i];
                        return Card(
                          child: InkWell(
                            onTap: () => Navigator.pushNamed(
                                context, '/members/profile',
                                arguments: m.id),
                            borderRadius: BorderRadius.circular(12),
                            child: Padding(
                              padding: const EdgeInsets.all(12),
                              child: Column(
                                mainAxisAlignment:
                                    MainAxisAlignment.center,
                                children: [
                                  m.photo != null
                                      ? ClipRRect(
                                          borderRadius:
                                              BorderRadius.circular(30),
                                          child: Image.network(
                                            'https://dobay2k.unaux.com/oldboys/uploads/${m.photo}',
                                            width: 60,
                                            height: 60,
                                            fit: BoxFit.cover,
                                            errorBuilder: (_, __, ___) =>
                                                const Icon(Icons.person,
                                                    size: 60,
                                                    color: Colors.grey),
                                          ),
                                        )
                                      : const Icon(Icons.person,
                                          size: 60,
                                          color: Colors.grey),
                                  const SizedBox(height: 8),
                                  Text(m.fullName,
                                      textAlign: TextAlign.center,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.bold)),
                                  if (m.graduationYear != null)
                                    Text('Class of ${m.graduationYear}',
                                        style: const TextStyle(
                                            color: Colors.grey,
                                            fontSize: 12)),
                                ],
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
