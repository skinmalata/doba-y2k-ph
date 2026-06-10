class Minute {
  final int id;
  final String title;
  final String content;
  final String meetingDate;
  final int authorId;
  final String? authorName;
  final String? createdAt;

  Minute({
    required this.id,
    required this.title,
    required this.content,
    required this.meetingDate,
    required this.authorId,
    this.authorName,
    this.createdAt,
  });

  factory Minute.fromJson(Map<String, dynamic> json) => Minute(
        id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
        title: json['title'] ?? '',
        content: json['content'] ?? '',
        meetingDate: json['meeting_date'] ?? '',
        authorId: json['author_id'] is int
            ? json['author_id']
            : int.parse(json['author_id'].toString()),
        authorName: json['author_name'],
        createdAt: json['created_at'],
      );
}
