import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/date_symbol_data_local.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import 'app_router.dart';
import 'core/config/app_config.dart';
import 'core/notifications/notification_service.dart';
import 'core/push/push_service.dart';
import 'core/theme/app_theme.dart';
import 'core/widgets/app_lock_gate.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID');
  await NotificationService().initialize();

  final container = ProviderContainer();
  if (AppConfig.pushEnabled) {
    await container.read(pushServiceProvider).initialize();
  }

  runApp(
    UncontrolledProviderScope(
      container: container,
      child: const EsppApp(),
    ),
  );
}

class EsppApp extends ConsumerWidget {
  const EsppApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => ShadApp.router(
        title: 'E-SPP Sekolah',
        debugShowCheckedModeBanner: false,
        theme: AppTheme.light(),
        darkTheme: AppTheme.dark(),
        themeMode: ThemeMode.system,
        routerConfig: ref.watch(routerProvider),
        locale: const Locale('id', 'ID'),
        supportedLocales: const [Locale('id', 'ID')],
        localizationsDelegates: const [
          GlobalMaterialLocalizations.delegate,
          GlobalWidgetsLocalizations.delegate,
          GlobalCupertinoLocalizations.delegate,
        ],
        builder: (context, child) => AppLockGate(
          child: child ?? const SizedBox.shrink(),
        ),
      );
}
