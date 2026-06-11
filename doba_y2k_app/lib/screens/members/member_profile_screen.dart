import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../models/member.dart';
import 'edit_profile_screen.dart';

class MemberProfileScreen extends StatefulWidget {
  final int memberId;
  const MemberProfileScreen({super.key, required this.memberId});

  @override
  State<MemberProfileScreen> createState() => _MemberProfileScreenState();
}

class _MemberProfileScreenState extends State<MemberProfileScreen> {
  Member? _member;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final token = await AuthService.getToken();
      final res = await ApiService.get('members/profile.php?id=${widget.memberId}',
          token: token);
      if (res['success'] == true) {
        setState(() {
          _member = Member.fromJson(res['member']);
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
        title: const Text('Profile'),
        actions: [
          IconButton(
            icon: const Icon(Icons.edit),
            onPressed: () async {
              if (_member == null) return;
              final result = await Navigator.push<bool>(
                context,
                MaterialPageRoute(
                  builder: (_) => EditProfileScreen(member: _member!),
                ),
              );
              if (result == true) _load();
            },
          ),
        ],
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _member == null
              ? const Center(child: Text('Member not found'))
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    children: [
                      CircleAvatar(
                        radius: 60,
                        backgroundImage: _member!.photo != null
                            ? NetworkImage(
                                'https://dobay2k.unaux.com/oldboys/uploads/${_member!.photo}')
                            : null,
                        child: _member!.photo == null
                            ? const Icon(Icons.person, size: 60)
                            : null,
                      ),
                      const SizedBox(height: 16),
                      Text(_member!.fullName,
                          style: const TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF1a1a2e))),
                      if (_member!.graduationYear != null)
                        Text('Class of ${_member!.graduationYear}',
                            style: const TextStyle(color: Colors.grey)),
                      const SizedBox(height: 24),
                      Card(
                        child: Column(
                          children: [
                            _infoRow(Icons.person, 'Username',
                                _member!.username),
                            _infoRow(
                                Icons.email, 'Email', _member!.email ?? '—'),
                            if (_member!.phone != null)
                              _infoRow(
                                  Icons.phone, 'Phone', _member!.phone!),
                            if (_member!.bio != null)
                              _infoRow(
                                  Icons.info, 'Bio', _member!.bio!),
                          ],
                        ),
                      ),
                      if (_member!.status == 'pending')
                        Container(
                          margin: const EdgeInsets.only(top: 16),
                          padding: const EdgeInsets.symmetric(
                              horizontal: 16, vertical: 8),
                          decoration: BoxDecoration(
                            color: Colors.orange.shade50,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: const Text('Pending Approval',
                              style: TextStyle(color: Colors.orange)),
                        ),
                    ],
                  ),
                ),
    );
  }

  Widget _infoRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        children: [
          Icon(icon, color: const Color(0xFFd4af37), size: 20),
          const SizedBox(width: 12),
          SizedBox(
              width: 80,
              child: Text(label,
                  style: const TextStyle(
                      fontWeight: FontWeight.bold, color: Colors.grey))),
          Expanded(child: Text(value)),
        ],
      ),
    );
  }
}
