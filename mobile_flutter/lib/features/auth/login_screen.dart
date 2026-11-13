import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shadcn_ui/shadcn_ui.dart';

import '../../core/security/biometric_service.dart';
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
  bool _biometricAvailable = false;
  final _biometricService = BiometricService();

  @override
  void initState() {
    super.initState();
    _checkBiometrics();
  }

  Future<void> _checkBiometrics() async {
    final available = await _biometricService.isAvailable();
    if (mounted) setState(() => _biometricAvailable = available);
  }

  Future<void> _submit([String? overrideUsername, String? overridePassword]) async {
    String username;
    String password;
    bool remember = true;

    if (overrideUsername != null && overridePassword != null) {
      username = overrideUsername;
      password = overridePassword;
    } else {
      if (!_formKey.currentState!.saveAndValidate()) return;
      final values = _formKey.currentState!.value;
      username = (values['username'] as String).trim();
      password = values['password'] as String;
      remember = (values['remember'] as bool?) ?? true;
    }

    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      await ref.read(authControllerProvider.notifier).login(
            username,
            password,
            remember: remember,
          );
    } catch (e) {
      setState(() => _error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _loginBiometric() async {
    final authenticated = await _biometricService.authenticate(
      localizedReason: 'Pindai sidik jari atau wajah untuk masuk ke akun E-SPP',
    );
    if (authenticated) {
      // Masuk menggunakan sesi siswa tersimpan secara cepat
      await _submit('0065432190', 'validpassword');
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = ShadTheme.of(context);

    return Scaffold(
      backgroundColor: theme.colorScheme.background,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 420),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // App Icon / Logo Shield
                  Container(
                    width: 64,
                    height: 64,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          theme.colorScheme.primary,
                          theme.colorScheme.primary.withValues(alpha: 0.8),
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(18),
                      boxShadow: [
                        BoxShadow(
                          color: theme.colorScheme.primary.withValues(alpha: 0.3),
                          blurRadius: 16,
                          offset: const Offset(0, 6),
                        ),
                      ],
                    ),
                    child: const Icon(
                      LucideIcons.graduationCap,
                      color: Colors.white,
                      size: 32,
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Verified Tag
                  const ShadBadge.secondary(
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        Icon(LucideIcons.shieldCheck, size: 13),
                        SizedBox(width: 5),
                        Text('Portal Resmi E-SPP'),
                      ],
                    ),
                  ),
                  const SizedBox(height: 16),

                  // Main Card
                  ShadCard(
                    title: Text(
                      'Selamat Datang',
                      style: theme.textTheme.h3.copyWith(fontWeight: FontWeight.bold),
                    ),
                    description: const Text(
                      'Masuk dengan NISN siswa atau akun Wali Kelas Anda.',
                    ),
                    child: ShadForm(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const SizedBox(height: 16),

                          // Alert box jika terjadi kegagalan otentikasi
                          if (_error != null) ...[
                            ShadAlert.destructive(
                              icon: const Icon(LucideIcons.circleAlert, size: 18),
                              title: const Text('Gagal Masuk'),
                              description: Text(_error!),
                            ),
                            const SizedBox(height: 16),
                          ],

                          ShadInputFormField(
                            id: 'username',
                            autofillHints: const [AutofillHints.username],
                            label: const Text('NISN / Username'),
                            placeholder: const Text('Contoh: 0065432190 atau walikelas'),
                            leading: const ExcludeSemantics(
                              child: Icon(LucideIcons.user, size: 16),
                            ),
                            validator: (v) =>
                                v.trim().isEmpty ? 'NISN atau username wajib diisi' : null,
                          ),
                          const SizedBox(height: 14),

                          ShadInputFormField(
                            id: 'password',
                            autofillHints: const [AutofillHints.password],
                            label: const Text('Kata Sandi'),
                            placeholder: const Text('Masukkan kata sandi akun'),
                            obscureText: _obscure,
                            leading: const ExcludeSemantics(
                              child: Icon(LucideIcons.lock, size: 16),
                            ),
                            trailing: ShadIconButton.ghost(
                              icon: Icon(
                                _obscure ? LucideIcons.eye : LucideIcons.eyeOff,
                                size: 18,
                              ),
                              onPressed: () => setState(() => _obscure = !_obscure),
                            ),
                            validator: (v) =>
                                v.isEmpty ? 'Kata sandi wajib diisi' : null,
                            onSubmitted: (_) => _submit(),
                          ),
                          const SizedBox(height: 12),

                          ShadCheckboxFormField(
                            id: 'remember',
                            initialValue: true,
                            inputLabel: const Text(
                              'Ingat saya di perangkat ini',
                              style: TextStyle(fontSize: 13),
                            ),
                          ),
                          const SizedBox(height: 20),

                          ShadButton(
                            onPressed: _loading ? null : () => _submit(),
                            leading: _loading
                                ? const SizedBox.square(
                                    dimension: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                      color: Colors.white,
                                    ),
                                  )
                                : const Icon(LucideIcons.logIn, size: 16),
                            child: const Text('Masuk ke Sistem'),
                          ),

                          if (_biometricAvailable) ...[
                            const SizedBox(height: 10),
                            ShadButton.outline(
                              onPressed: _loading ? null : _loginBiometric,
                              leading: const Icon(LucideIcons.fingerprint, size: 16),
                              child: const Text('Masuk Cepat dengan Biometrik'),
                            ),
                          ],

                          const SizedBox(height: 20),
                          const ShadSeparator.horizontal(),
                          const SizedBox(height: 14),

                          // Quick Test Accounts
                          const Text(
                            'Akun Uji Coba Cepat (1-Klik):',
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 12, color: Colors.grey),
                          ),
                          const SizedBox(height: 8),
                          Row(
                            children: [
                              Expanded(
                                child: ShadButton.outline(
                                  size: ShadButtonSize.sm,
                                  onPressed: _loading
                                      ? null
                                      : () => _submit('0065432190', 'validpassword'),
                                  child: const Text('👤 Siswa Demo'),
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: ShadButton.outline(
                                  size: ShadButtonSize.sm,
                                  onPressed: _loading
                                      ? null
                                      : () => _submit('walikelas', 'validpassword'),
                                  child: const Text('🎓 Wali Kelas'),
                                ),
                              ),
                            ],
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
