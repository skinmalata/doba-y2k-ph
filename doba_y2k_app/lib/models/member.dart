class Member {
  final int id;
  final String username;
  final String firstName;
  final String lastName;
  final String? photo;
  final String? bio;
  final String? graduationYear;
  final String? phone;
  final String? email;
  final String role;
  final String? status;
  final String? createdAt;

  Member({
    required this.id,
    required this.username,
    required this.firstName,
    required this.lastName,
    this.photo,
    this.bio,
    this.graduationYear,
    this.phone,
    this.email,
    this.role = 'member',
    this.status,
    this.createdAt,
  });

  String get fullName => '$firstName $lastName';

  factory Member.fromJson(Map<String, dynamic> json) => Member(
        id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
        username: json['username'] ?? '',
        firstName: json['first_name'] ?? '',
        lastName: json['last_name'] ?? '',
        photo: json['photo'],
        bio: json['bio'],
        graduationYear: json['graduation_year']?.toString(),
        phone: json['phone'],
        email: json['email'],
        role: json['role'] ?? 'member',
        status: json['status'],
        createdAt: json['created_at'],
      );
}
