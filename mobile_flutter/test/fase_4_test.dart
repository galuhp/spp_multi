import 'package:espp_mobile/core/security/biometric_service.dart';
import 'package:espp_mobile/features/riwayat/receipt_pdf_service.dart';
import 'package:espp_mobile/features/tagihan/tagihan_models.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';

void main() async {
  TestWidgetsFlutterBinding.ensureInitialized();
  await initializeDateFormatting('id_ID');
  group('ReceiptPdfService - Terbilang Rupiah', () {
    test('Angka 0 menghasilkan Nol Rupiah', () {
      expect(ReceiptPdfService.terbilang(0), 'Nol Rupiah');
    });

    test('Angka 250000 menghasilkan Dua Ratus Lima Puluh Ribu Rupiah', () {
      expect(
        ReceiptPdfService.terbilang(250000),
        'Dua Ratus Lima Puluh Ribu Rupiah',
      );
    });

    test('Angka 1500000 menghasilkan Satu Juta Lima Ratus Ribu Rupiah', () {
      expect(
        ReceiptPdfService.terbilang(1500000),
        'Satu Juta Lima Ratus Ribu Rupiah',
      );
    });
  });

  group('ReceiptPdfService - Pembuatan Dokumen PDF', () {
    test('generateReceiptPdf menghasilkan berkas PDF valid (header %PDF)', () async {
      final item = PembayaranItem(
        kode: 'SPP-20261012-0105',
        tanggal: DateTime(2026, 10, 12, 9, 30),
        jumlah: 250000,
        metode: 'Transfer Bank (BCA)',
        petugas: 'Bendahara Sekolah',
        isVoid: false,
        tokenVerifikasi: 'a8f4c2e1b9',
      );

      final pdfBytes = await ReceiptPdfService.generateReceiptPdf(
        item: item,
        namaSiswa: 'M. Rizky Pratama',
        kelas: 'XII RPL 1',
        nisn: '0065432190',
      );

      expect(pdfBytes, isNotEmpty);
      // Valid PDF magic header bytes: %PDF- (0x25, 0x50, 0x44, 0x46)
      expect(pdfBytes.sublist(0, 4), [0x25, 0x50, 0x44, 0x46]);
    });
  });

  group('BiometricService - Graceful Degradation', () {
    test('isAvailable tidak melempar uncaught exception pada environment pengujian', () async {
      final bio = BiometricService();
      // Di headless test environment tidak ada hardware sensor
      final res = await bio.isAvailable();
      expect(res, isA<bool>());
    });
  });
}
