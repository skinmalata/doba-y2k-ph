import 'package:flutter/material.dart';
import '../../services/api_service.dart';
import '../../services/auth_service.dart';
import '../../models/transaction.dart';

class AccountsScreen extends StatefulWidget {
  const AccountsScreen({super.key});

  @override
  State<AccountsScreen> createState() => _AccountsScreenState();
}

class _AccountsScreenState extends State<AccountsScreen> {
  List<Transaction> _transactions = [];
  double _totalIncome = 0;
  double _totalExpense = 0;
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    try {
      final token = await AuthService.getToken();
      final res = await ApiService.get('accounts/index.php', token: token);
      if (res['success'] == true) {
        setState(() {
          _transactions = (res['transactions'] as List)
              .map((j) => Transaction.fromJson(j))
              .toList();
          _totalIncome = double.tryParse(res['total_income'].toString()) ?? 0;
          _totalExpense =
              double.tryParse(res['total_expense'].toString()) ?? 0;
          _loading = false;
        });
      }
    } catch (_) {}
    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    final balance = _totalIncome - _totalExpense;
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(title: const Text('Accounts')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _load,
              child: ListView(
                padding: const EdgeInsets.all(12),
                children: [
                  Card(
                    color: const Color(0xFF1a1a2e),
                    child: Padding(
                      padding: const EdgeInsets.all(20),
                      child: Column(
                        children: [
                          const Text('Balance',
                              style: TextStyle(color: Colors.white70)),
                          Text('₦${balance.toStringAsFixed(2)}',
                              style: const TextStyle(
                                  fontSize: 32,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.white)),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      _summaryCard('Income', _totalIncome, Colors.green),
                      const SizedBox(width: 8),
                      _summaryCard('Expense', _totalExpense, Colors.red),
                    ],
                  ),
                  const SizedBox(height: 16),
                  const Text('Recent Transactions',
                      style: TextStyle(
                          fontSize: 18, fontWeight: FontWeight.bold)),
                  ..._transactions.map((t) => Card(
                        margin: const EdgeInsets.only(top: 4),
                        child: ListTile(
                          title: Text(t.description,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis),
                          subtitle: Text(
                            '${t.createdAt}${t.memberName != null ? ' · ${t.memberName}' : ''}',
                            style: const TextStyle(fontSize: 12),
                          ),
                          trailing: Text(
                            '₦${t.amount.toStringAsFixed(2)}',
                            style: TextStyle(
                              fontWeight: FontWeight.bold,
                              color: t.type == 'income'
                                  ? Colors.green
                                  : Colors.red,
                            ),
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
          padding: const EdgeInsets.all(12),
          child: Column(
            children: [
              Text(label, style: const TextStyle(color: Colors.grey)),
              Text('₦${amount.toStringAsFixed(2)}',
                  style: TextStyle(
                      fontSize: 20,
                      fontWeight: FontWeight.bold,
                      color: color)),
            ],
          ),
        ),
      ),
    );
  }
}
