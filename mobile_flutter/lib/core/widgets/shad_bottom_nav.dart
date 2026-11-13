import 'package:flutter/material.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

class ShadBottomNavItem {
  const ShadBottomNavItem({
    required this.icon,
    required this.activeIcon,
    required this.label,
  });

  final IconData icon;
  final IconData activeIcon;
  final String label;
}

/// Navigasi bawah bergaya modern Shadcn UI dengan active pill dan animasi micro-interaction.
class ShadBottomNav extends StatelessWidget {
  const ShadBottomNav({
    super.key,
    required this.currentIndex,
    required this.onTap,
    required this.items,
  });

  final int currentIndex;
  final ValueChanged<int> onTap;
  final List<ShadBottomNavItem> items;

  @override
  Widget build(BuildContext context) {
    final theme = ShadTheme.of(context);
    final bottomPadding = MediaQuery.paddingOf(context).bottom;

    return Container(
      decoration: BoxDecoration(
        color: theme.colorScheme.card,
        border: Border(
          top: BorderSide(color: theme.colorScheme.border, width: 1),
        ),
      ),
      padding: EdgeInsets.fromLTRB(12, 8, 12, bottomPadding > 0 ? bottomPadding : 10),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: List.generate(items.length, (index) {
          final item = items[index];
          final isActive = index == currentIndex;

          return Expanded(
            child: Semantics(
              button: true,
              selected: isActive,
              label: '${item.label}, tab ${index + 1} dari ${items.length}',
              child: GestureDetector(
                behavior: HitTestBehavior.opaque,
                onTap: () => onTap(index),
                child: AnimatedContainer(
                  duration: const Duration(milliseconds: 200),
                  curve: Curves.easeInOut,
                  padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 8),
                  decoration: BoxDecoration(
                    color: isActive
                        ? theme.colorScheme.primary.withValues(alpha: 0.12)
                        : Colors.transparent,
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(
                        isActive ? item.activeIcon : item.icon,
                        size: 20,
                        color: isActive
                            ? theme.colorScheme.primary
                            : theme.colorScheme.mutedForeground,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        item.label,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
                          color: isActive
                              ? theme.colorScheme.primary
                              : theme.colorScheme.mutedForeground,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          );
        }),
      ),
    );
  }
}
