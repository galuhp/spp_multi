import 'package:flutter/material.dart';
import 'package:shadcn_ui/shadcn_ui.dart';
import 'package:shimmer/shimmer.dart';

/// Skeleton kotak untuk state loading.
class SkeletonBox extends StatelessWidget {
  const SkeletonBox({super.key, this.height = 120});

  final double height;

  @override
  Widget build(BuildContext context) {
    final dark = Theme.of(context).brightness == Brightness.dark;
    final base = dark ? const Color(0xFF1E293B) : Colors.grey.shade300;
    final box = Container(
      height: height,
      decoration: BoxDecoration(
        color: dark ? const Color(0xFF1E293B) : Colors.white,
        borderRadius: BorderRadius.circular(14),
      ),
    );
    if (MediaQuery.disableAnimationsOf(context)) {
      return Semantics(label: 'Memuat', child: box);
    }
    return Semantics(
      label: 'Memuat',
      child: Shimmer.fromColors(
        baseColor: base,
        highlightColor: dark ? const Color(0xFF334155) : Colors.grey.shade100,
        child: box,
      ),
    );
  }
}

class ErrorView extends StatelessWidget {
  const ErrorView({super.key, required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => ShadCard(
        child: Column(
          children: [
            ExcludeSemantics(
              child: Icon(LucideIcons.triangleAlert,
                  color: ShadTheme.of(context).colorScheme.destructive),
            ),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 12),
            ShadButton.outline(onPressed: onRetry, child: const Text('Coba Lagi')),
          ],
        ),
      );
}

class EmptyView extends StatelessWidget {
  const EmptyView({super.key, required this.message});

  final String message;

  @override
  Widget build(BuildContext context) => Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          children: [
            const ExcludeSemantics(child: Icon(LucideIcons.inbox, size: 40)),
            const SizedBox(height: 8),
            Text(message, textAlign: TextAlign.center),
          ],
        ),
      );
}
