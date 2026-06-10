import 'package:flutter/material.dart';
import 'services/auth_service.dart';
import 'screens/splash_screen.dart';
import 'screens/login_screen.dart';
import 'screens/register_screen.dart';
import 'screens/home_screen.dart';
import 'screens/members/member_list_screen.dart';
import 'screens/members/member_profile_screen.dart';
import 'screens/minutes/minute_list_screen.dart';
import 'screens/minutes/minute_view_screen.dart';
import 'screens/levies/levy_list_screen.dart';
import 'screens/accounts/accounts_screen.dart';
import 'screens/admin/admin_members_screen.dart';

void main() => runApp(const DobaApp());

class DobaApp extends StatelessWidget {
  const DobaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'DOBA Y2k',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF1a1a2e),
          primary: const Color(0xFF1a1a2e),
          secondary: const Color(0xFFd4af37),
        ),
        scaffoldBackgroundColor: Colors.white,
        appBarTheme: const AppBarTheme(
          backgroundColor: Color(0xFF1a1a2e),
          foregroundColor: Colors.white,
          centerTitle: true,
        ),
        useMaterial3: true,
      ),
      home: const SplashScreen(),
      onGenerateRoute: (settings) {
        switch (settings.name) {
          case '/login':
            return MaterialPageRoute(builder: (_) => const LoginScreen());
          case '/register':
            return MaterialPageRoute(builder: (_) => const RegisterScreen());
          case '/home':
            return MaterialPageRoute(builder: (_) => const HomeScreen());
          case '/members':
            return MaterialPageRoute(
                builder: (_) => const MemberListScreen());
          case '/members/profile':
            final id = settings.arguments as int;
            return MaterialPageRoute(
                builder: (_) =>
                    MemberProfileScreen(memberId: id));
          case '/minutes':
            return MaterialPageRoute(
                builder: (_) => const MinuteListScreen());
          case '/minutes/view':
            final id = settings.arguments as int;
            return MaterialPageRoute(
                builder: (_) => MinuteViewScreen(minuteId: id));
          case '/levies':
            return MaterialPageRoute(
                builder: (_) => const LevyListScreen());
          case '/accounts':
            return MaterialPageRoute(
                builder: (_) => const AccountsScreen());
          case '/admin/members':
            return MaterialPageRoute(
                builder: (_) => const AdminMembersScreen());
          default:
            return MaterialPageRoute(builder: (_) => const HomeScreen());
        }
      },
    );
  }
}
