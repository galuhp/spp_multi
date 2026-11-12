class AuthSession {
  AuthSession({required this.token, required this.role, required this.name});

  final String token;
  final String role;
  final String name;

  factory AuthSession.fromJson(Map<String, dynamic> json) => AuthSession(
        token: json['token'] as String,
        role: (json['role'] ?? json['user']?['role'] ?? '') as String,
        name: (json['name'] ?? json['user']?['name'] ?? '') as String,
      );
}
