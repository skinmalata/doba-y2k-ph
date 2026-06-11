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
                  ..._levies.map((l) => _buildLevyCard(l)),
                ],
              ),
            ),
    );
  }

  Widget _buildLevyCard(Levy l) {
    final pct = l.amount > 0 ? (100 * l.userPaid / l.amount).clamp(0, 100) : 0.0;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(l.title,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                ),
                Chip(
                  label: Text(
                    l.isFullyPaid
                        ? 'Paid'
                        : l.userPaid > 0
                            ? 'Partial'
                            : 'Owing',
                    style: const TextStyle(color: Colors.white, fontSize: 12),
                  ),
                  backgroundColor: l.isFullyPaid
                      ? Colors.green
                      : l.userPaid > 0
                          ? Colors.orange
                          : Colors.red,
                  padding: EdgeInsets.zero,
                  materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                ),
              ],
            ),
            const SizedBox(height: 4),
            Text(
              '₦${l.amount.toStringAsFixed(2)}'
              '${l.dueDate != null ? ' · Due: ${l.dueDate}' : ''}',
              style: const TextStyle(color: Colors.grey),
            ),
            if (l.userPaid > 0) ...[
              const SizedBox(height: 6),
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: pct / 100,
                  backgroundColor: Colors.grey[200],
                  color: l.isFullyPaid ? Colors.green : Colors.orange,
                  minHeight: 8,
                ),
              ),
              const SizedBox(height: 2),
              Text(
                '₦${l.userPaid.toStringAsFixed(0)} / ₦${l.amount.toStringAsFixed(0)}',
                style: const TextStyle(fontSize: 11, color: Colors.grey),
              ),
            ],
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
