import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

/// Design tokens "Trusted Fintech" (lihat `prompts/02_ui_options.md`).
class AppColors {
  AppColors._();

  static const primary = Color(0xFF1E3A8A);
  // Teks status >= 4.5:1 di atas background pastel (WCAG AA).
  static const success = Color(0xFF047857);
  static const successBg = Color(0xFFECFDF5);
  static const successDark = Color(0xFF6EE7B7);
  static const warning = Color(0xFFB45309);
  static const warningBg = Color(0xFFFFFBEB);
  static const warningDark = Color(0xFFFCD34D);
  static const danger = Color(0xFFBE123C);
  static const dangerBg = Color(0xFFFFF1F2);
  static const dangerDark = Color(0xFFFDA4AF);
  static const subsidi = Color(0xFF6D28D9);
  static const subsidiBg = Color(0xFFF5F3FF);
  static const subsidiDark = Color(0xFFC4B5FD);
  static const neutral = Color(0xFF475569);
  static const neutralBg = Color(0xFFF1F5F9);
  static const neutralDark = Color(0xFFCBD5E1);
  static const canvas = Color(0xFFF8FAFC);
  static const border = Color(0xFFE2E8F0);
  static const headerStart = Color(0xFF1E3A8A);
  static const headerEnd = Color(0xFF3B5BDB);
  static const onHeader = Colors.white;
  static const onHeaderMuted = Color(0xFFDBE4FF);
}

class AppTheme {
  AppTheme._();

  /// Gaya monospace (IBM Plex Mono) untuk nomor kuitansi & kode transaksi.
  static TextStyle mono({FontWeight? fontWeight}) => GoogleFonts.ibmPlexMono(
        fontWeight: fontWeight,
        fontFeatures: const [FontFeature.tabularFigures()],
      );

  static ShadThemeData light() => ShadThemeData(
        brightness: Brightness.light,
        colorScheme: const ShadSlateColorScheme.light(
          primary: AppColors.primary,
          primaryForeground: Colors.white,
          background: AppColors.canvas,
          border: AppColors.border,
          destructive: AppColors.danger,
        ),
        radius: BorderRadius.circular(14),
        textTheme: ShadTextTheme.fromGoogleFont(GoogleFonts.ibmPlexSans),
      );

  static ShadThemeData dark() => ShadThemeData(
        brightness: Brightness.dark,
        colorScheme: const ShadSlateColorScheme.dark(
          primary: Color(0xFF93B4FF),
          primaryForeground: Color(0xFF0B1220),
          destructive: AppColors.dangerDark,
        ),
        radius: BorderRadius.circular(14),
        textTheme: ShadTextTheme.fromGoogleFont(GoogleFonts.ibmPlexSans),
      );
}
