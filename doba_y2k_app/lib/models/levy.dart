class Levy {
  final int id;
  final String title;
  final double amount;
  final String? description;
  final String? dueDate;
  final String status;
  double userPaid;
  double remaining;
  bool isFullyPaid;
  final String? paidAt;

  Levy({
    required this.id,
    required this.title,
    required this.amount,
    this.description,
    this.dueDate,
    this.status = 'active',
    this.userPaid = 0,
    this.remaining = 0,
    this.isFullyPaid = false,
    this.paidAt,
  });

  factory Levy.fromJson(Map<String, dynamic> json) {
    final amount = double.tryParse(json['amount'].toString()) ?? 0;
    final userPaid = double.tryParse(json['user_paid']?.toString() ?? '0') ?? 0;
    final remaining = double.tryParse(json['remaining']?.toString() ?? '0') ?? (amount - userPaid);
    return Levy(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      title: json['title'] ?? '',
      amount: amount,
      description: json['description'],
      dueDate: json['due_date'],
      status: json['status'] ?? 'active',
      userPaid: userPaid,
      remaining: remaining,
      isFullyPaid: json['is_fully_paid'] == true || json['is_fully_paid'] == 1,
      paidAt: json['paid_at'],
    );
  }
}
