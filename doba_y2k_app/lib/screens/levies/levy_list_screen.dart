import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../models/levy.dart';

class LevyListScreen extends StatefulWidget {
  const LevyListScreen({super.key});

  @override
  State<LevyListScreen> createState() => _LevyListScreenState();
}

class _LevyListScreenState extends State<LevyListScreen> {
  List<Levy> _levies = [];
  double _totalPaid = 0;
  double _totalOwing = 0;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final token = await AuthService.getToken();
      final res = await ApiService.get('levies/index.php', token: token);
      if (res['success'] == true) {
        setState(() {
          _levies =
              (res['levies'] as List).map((j) => Levy.fromJson(j)).toList();
          _totalPaid = double.tryParse(res['total_paid'].toString()) ?? 0;
          _totalOwing = double.tryParse(res['total_owing'].toString()) ?? 0;
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
      appBar: AppBar(title: const Text('Levies')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(12),
                children: [
                  Row(
                    children: [
                      _summaryCard('Paid', _totalPaid, Colors.green),
                      const SizedBox(width: 8),
                      _summaryCard('Owing', _totalOwing, Colors.red),
                    ],
                  ),
                  const SizedBox(height: 16),
                  ..._levies.map((l) => Card(
                        margin: const EdgeInsets.only(bottom: 8),
                        child: ListTile(
                          title: Text(l.title,
                              style:
                                  const TextStyle(fontWeight: FontWeight.bold)),
                          subtitle: Text(
                            '₦${l.amount.toStringAsFixed(2)}'
                            '${l.dueDate != null ? ' · Due: ${l.dueDate}' : ''}',
                          ),
                          trailing: l.isPaid
                              ? const Chip(
                                  label: Text('Paid',
                                      style: TextStyle(
                                          color: Colors.white, fontSize: 12)),
                                  backgroundColor: Colors.green,
                                  padding: EdgeInsets.zero,
                                  materialTapTargetSize:
                                      MaterialTapTargetSize.shrinkWrap,
                                )
                              : const Chip(
                                  label: Text('Owing',
                                      style: TextStyle(
                                          color: Colors.white, fontSize: 12)),
                                  backgroundColor: Colors.orange,
                                  padding: EdgeInsets.zero,
                                  materialTapTargetSize:
                                      MaterialTapTargetSize.shrinkWrap,
                                ),
                        ),
                      )),
                ],
              ),
            ),
    );
  }

  Widget _summaryCard(String label, double amount, Color color) {
    return Expanded(
      child: Card(
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            children: [
              Text('Total $label',
                  style: const TextStyle(color: Colors.grey)),
              Text('₦${amount.toStringAsFixed(2)}',
                  style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      color: color)),
            ],
          ),
        ),
      ),
    );
  }
}
