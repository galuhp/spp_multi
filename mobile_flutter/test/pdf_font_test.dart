import 'package:espp_mobile/features/riwayat/receipt_pdf_service.dart';
import 'package:espp_mobile/features/tagihan/tagihan_models.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:intl/date_symbol_data_local.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUpAll(() async {
    await initializeDateFormatting('id_ID');
  });

  test('PDF kuitansi memakai font lokal dan aman untuk karakter non-Latin-1',
      () async {
    final item = PembayaranItem(
      kode: 'SPP-20261113-0001',
      tanggal: DateTime(2026, 11, 13),
      jumlah: 250000,
      metode: 'Transfer Bank – BCA',
      petugas: 'Sri Wulandari, S.Kom',
      isVoid: false,
      tokenVerifikasi: 'abc123',
    );

    final bytes = await ReceiptPdfService.generateReceiptPdf(
      item: item,
      namaSiswa: 'Ayu Putri Maharani – XII',
      kelas: 'XII Rekayasa Perangkat Lunak 1',
      nisn: '0065432190',
    );

    expect(bytes.length, greaterThan(1000));
    // Header berkas PDF valid.
    expect(String.fromCharCodes(bytes.take(4)), '%PDF');
  });
}
