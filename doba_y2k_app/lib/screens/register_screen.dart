import 'package:flutter/material.dart';
import '../services/api_service.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _usernameCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _firstCtrl = TextEditingController();
  final _lastCtrl = TextEditingController();
  final _passCtrl = TextEditingController();
  final _confirmCtrl = TextEditingController();
  String _graduationYear = '';
  bool _loading = false;

  Future<void> _register() async {
    if (_usernameCtrl.text.isEmpty ||
        _emailCtrl.text.isEmpty ||
        _passCtrl.text.isEmpty) {
      _showError('Please fill all required fields');
      return;
    }
    if (_passCtrl.text != _confirmCtrl.text) {
      _showError('Passwords do not match');
      return;
    }
    if (_passCtrl.text.length < 6) {
      _showError('Password must be at least 6 characters');
      return;
    }
    setState(() => _loading = true);
    try {
      final res = await ApiService.post('auth/register.php', body: {
        'username': _usernameCtrl.text,
        'email': _emailCtrl.text,
        'password': _passCtrl.text,
        'first_name': _firstCtrl.text,
        'last_name': _lastCtrl.text,
        'graduation_year': _graduationYear,
      });
      if (!mounted) return;
      if (res['success'] == true) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
          content: Text(res['message'] ?? 'Registration submitted!'),
          backgroundColor: Colors.green,
        ));
        Navigator.pushReplacementNamed(context, '/login');
      } else {
        _showError(res['message'] ?? 'Registration failed');
      }
    } catch (e) {
      _showError('Connection error');
    }
    setState(() => _loading = false);
  }

  void _showError(String msg) {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      content: Text(msg),
      backgroundColor: Colors.red,
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(title: const Text('Register')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            Image.asset('assets/splash.jpg', width: 80, height: 80),
            const SizedBox(height: 16),
            const Text('Join DOBA 2000 Set',
                style: TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1a1a2e))),
            const SizedBox(height: 24),
            Row(
              children: [
                Expanded(
                    child: TextField(
                        controller: _firstCtrl,
                        decoration: const InputDecoration(
                            labelText: 'First Name',
                            border: OutlineInputBorder()))),
                const SizedBox(width: 12),
                Expanded(
                    child: TextField(
                        controller: _lastCtrl,
                        decoration: const InputDecoration(
                            labelText: 'Last Name',
                            border: OutlineInputBorder()))),
              ],
            ),
            const SizedBox(height: 12),
            TextField(
                controller: _usernameCtrl,
                decoration: const InputDecoration(
                    labelText: 'Username *',
                    prefixIcon: Icon(Icons.person),
                    border: OutlineInputBorder())),
            const SizedBox(height: 12),
            TextField(
                controller: _emailCtrl,
                keyboardType: TextInputType.emailAddress,
                decoration: const InputDecoration(
                    labelText: 'Email *',
                    prefixIcon: Icon(Icons.email),
                    border: OutlineInputBorder())),
            const SizedBox(height: 12),
            DropdownButtonFormField<String>(
              value: _graduationYear.isEmpty ? null : _graduationYear,
              decoration: const InputDecoration(
                  labelText: 'Graduation Year',
                  border: OutlineInputBorder()),
              items: [
                const DropdownMenuItem(value: '', child: Text('Select Year')),
                ...List.generate(
                    DateTime.now().year - 1960 + 1,
                    (i) => (DateTime.now().year - i).toString()).map((y) =>
                    DropdownMenuItem(value: y, child: Text(y))),
              ],
              onChanged: (v) => _graduationYear = v ?? '',
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                    child: TextField(
                        controller: _passCtrl,
                        obscureText: true,
                        decoration: const InputDecoration(
                            labelText: 'Password *',
                            border: OutlineInputBorder()))),
                const SizedBox(width: 12),
                Expanded(
                    child: TextField(
                        controller: _confirmCtrl,
                        obscureText: true,
                        decoration: const InputDecoration(
                            labelText: 'Confirm *',
                            border: OutlineInputBorder()))),
              ],
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 48,
              child: ElevatedButton(
                onPressed: _loading ? null : _register,
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF1a1a2e),
                  foregroundColor: Colors.white,
                ),
                child: _loading
                    ? const SizedBox(
                        width: 24,
                        height: 24,
                        child: CircularProgressIndicator(
                            strokeWidth: 2, color: Colors.white))
                    : const Text('Register', style: TextStyle(fontSize: 16)),
              ),
            ),
            const SizedBox(height: 12),
            TextButton(
              onPressed: () => Navigator.pushReplacementNamed(context, '/login'),
              child: const Text('Already have an account? Login'),
            ),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _usernameCtrl.dispose();
    _emailCtrl.dispose();
    _firstCtrl.dispose();
    _lastCtrl.dispose();
    _passCtrl.dispose();
    _confirmCtrl.dispose();
    super.dispose();
  }
}
