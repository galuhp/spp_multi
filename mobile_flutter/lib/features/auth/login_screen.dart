import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import 'auth_controller.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<ShadFormState>();
  bool _loading = false;
  bool _obscure = true;
  String? _error;

  Future<void> _submit() async {
    if (!_formKey.currentState!.saveAndValidate()) return;
    final values = _formKey.currentState!.value;
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      await ref.read(authControllerProvider.notifier).login(
            (values['username'] as String).trim(),
            values['password'] as String,
            remember: (values['remember'] as bool?) ?? true,
          );
    } catch (e) {
      setState(() => _error = e.toString());
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = ShadTheme.of(context);
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: ShadCard(
                title: Text('E-SPP Sekolah', style: theme.textTheme.h3),
                description: const Text('Masuk untuk melihat tagihan & kuitansi'),
                child: ShadForm(
                  key: _formKey,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const SizedBox(height: 16),
                      ShadInputFormField(
                        id: 'username',
                        autofillHints: const [AutofillHints.username],
                        label: const Text('NISN / Username'),
                        leading: const ExcludeSemantics(
                            child: Icon(LucideIcons.user, size: 16)),
                        validator: (v) =>
                            v.trim().isEmpty ? 'Wajib diisi' : null,
                      ),
                      const SizedBox(height: 12),
                      ShadInputFormField(
                        id: 'password',
                        autofillHints: const [AutofillHints.password],
                        label: const Text('Password'),
                        obscureText: _obscure,
                        leading: const ExcludeSemantics(
                            child: Icon(LucideIcons.lock, size: 16)),
                        trailing: IconButton(
                          tooltip: _obscure
                              ? 'Tampilkan password'
                              : 'Sembunyikan password',
                          constraints: const BoxConstraints.tightFor(
                              width: 48, height: 48),
                          padding: EdgeInsets.zero,
                          icon: Icon(
                            _obscure ? LucideIcons.eye : LucideIcons.eyeOff,
                            size: 18,
                          ),
                          onPressed: () => setState(() => _obscure = !_obscure),
                        ),
                        validator: (v) => v.isEmpty ? 'Wajib diisi' : null,
                        onSubmitted: (_) => _submit(),
                      ),
                      const SizedBox(height: 12),
                      ShadCheckboxFormField(
                        id: 'remember',
                        initialValue: true,
                        inputLabel: const Text('Ingat Saya'),
                      ),
                      if (_error != null) ...[
                        const SizedBox(height: 12),
                        Text(_error!,
                            style: TextStyle(
                                color: theme.colorScheme.destructive)),
                      ],
                      const SizedBox(height: 20),
                      ShadButton(
                        onPressed: _loading ? null : _submit,
                        child: _loading
                            ? const SizedBox.square(
                                dimension: 18,
                                child:
                                    CircularProgressIndicator(strokeWidth: 2))
                            : const Text('Masuk'),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
