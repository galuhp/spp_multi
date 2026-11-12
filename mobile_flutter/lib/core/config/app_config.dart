enum AppEnvironment { dev, prod, demo }

/// Konfigurasi environment aplikasi via `--dart-define`.
class AppConfig {
  AppConfig._();

  static const String _envName = String.fromEnvironment('ENV', defaultValue: 'demo');
  static const bool useMock = bool.fromEnvironment('USE_MOCK', defaultValue: true);

  static AppEnvironment get environment {
    switch (_envName.toLowerCase()) {
      case 'prod':
      case 'production':
        return AppEnvironment.prod;
      case 'dev':
      case 'development':
        return AppEnvironment.dev;
      default:
        return AppEnvironment.demo;
    }
  }

  static bool get isProduction => environment == AppEnvironment.prod;

  /// Memvalidasi URL di production agar wajib menggunakan HTTPS.
  static String sanitizeBaseUrl(String url) {
    if (isProduction && url.startsWith('http://')) {
      throw StateError('Production build wajib menggunakan HTTPS: $url');
    }
    return url;
  }
}
