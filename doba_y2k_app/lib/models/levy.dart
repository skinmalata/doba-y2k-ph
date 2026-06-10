class Levy {
  final int id;
  final String title;
  final double amount;
  final String? description;
  final String? dueDate;
  final String status;
  final bool isPaid;
  final String? paidAt;

  Levy({
    required this.id,
    required this.title,
    required this.amount,
    this.description,
    this.dueDate,
    this.status = 'active',
    this.isPaid = false,
    this.paidAt,
  });

  factory Levy.fromJson(Map<String, dynamic> json) => Levy(
        id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
        title: json['title'] ?? '',
        amount: double.tryParse(json['amount'].toString()) ?? 0,
        description: json['description'],
        dueDate: json['due_date'],
        status: json['status'] ?? 'active',
        isPaid: json['is_paid'] == 1 || json['is_paid'] == true,
        paidAt: json['paid_at'],
      );
}
