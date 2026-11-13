import 'package:flutter/material.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

/// App bar seragam bergaya Shadcn UI dengan border bawah dan tipografi presisi.
class ShadAppBar extends StatelessWidget implements PreferredSizeWidget {
  const ShadAppBar({
    super.key,
    required this.title,
    this.subtitle,
    this.leading,
    this.actions,
    this.showBorder = true,
  });

  final String title;
  final String? subtitle;
  final Widget? leading;
  final List<Widget>? actions;
  final bool showBorder;

  @override
  Size get preferredSize => const Size.fromHeight(56);

  @override
  Widget build(BuildContext context) {
    final theme = ShadTheme.of(context);
    final top = MediaQuery.paddingOf(context).top;

    return Container(
      padding: EdgeInsets.fromLTRB(12, top, 12, 0),
      height: preferredSize.height + top,
      decoration: BoxDecoration(
        color: theme.colorScheme.background,
        border: showBorder
            ? Border(
                bottom: BorderSide(
                  color: theme.colorScheme.border,
                  width: 1,
                ),
              )
            : null,
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          if (leading != null) ...[
            leading!,
            const SizedBox(width: 8),
          ] else if (Navigator.of(context).canPop()) ...[
            ShadIconButton.ghost(
              icon: const Icon(LucideIcons.arrowLeft, size: 18),
              onPressed: () => Navigator.of(context).maybePop(),
            ),
            const SizedBox(width: 8),
          ],
          Expanded(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: theme.textTheme.h4.copyWith(
                    fontWeight: FontWeight.w700,
                    fontSize: 18,
                  ),
                ),
                if (subtitle != null) ...[
                  const SizedBox(height: 2),
                  Text(
                    subtitle!,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.muted.copyWith(fontSize: 12),
                  ),
                ],
              ],
            ),
          ),
          if (actions != null) ...actions!,
        ],
      ),
    );
  }
}
