class Transaction {
  final int id;
  final String type;
  final String category;
  final String description;
  final double amount;
  final int? memberId;
  final String? memberName;
  final String? recordedByName;
  final String createdAt;

  Transaction({
    required this.id,
    required this.type,
    required this.category,
    required this.description,
    required this.amount,
    this.memberId,
    this.memberName,
    this.recordedByName,
    required this.createdAt,
  });

  factory Transaction.fromJson(Map<String, dynamic> json) => Transaction(
        id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
        type: json['type'] ?? '',
        category: json['category'] ?? '',
        description: json['description'] ?? '',
        amount: double.tryParse(json['amount'].toString()) ?? 0,
        memberId: json['member_id'] != null
            ? int.tryParse(json['member_id'].toString())
            : null,
        memberName: json['member_name'],
        recordedByName: json['recorded_by_name'],
        createdAt: json['created_at'] ?? '',
      );
}
