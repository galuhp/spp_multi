import 'dart:typed_data';

import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

import '../../core/constants/api_constants.dart';
import '../../core/utils/formatters.dart';
import '../tagihan/tagihan_models.dart';

/// Service pembuat dokumen PDF resmi kuitansi pembayaran SPP.
class ReceiptPdfService {
  ReceiptPdfService._();

  /// Konversi nominal angka ke kalimat terbilang Rupiah otomatis.
  static String terbilang(num number) {
    final n = number.toInt();
    if (n == 0) return 'Nol Rupiah';
    if (n < 0) return 'Minus ${terbilang(-n)}';

    const satuan = [
      '',
      'Satu',
      'Dua',
      'Tiga',
      'Empat',
      'Lima',
      'Enam',
      'Tujuh',
      'Delapan',
      'Sembilan',
      'Sepuluh',
      'Sebelas'
    ];

    String hitung(int val) {
      if (val < 12) {
        return satuan[val];
      } else if (val < 20) {
        return '${satuan[val - 10]} Belas';
      } else if (val < 100) {
        return '${satuan[val ~/ 10]} Puluh ${hitung(val % 10)}'.trim();
      } else if (val < 200) {
        return 'Seratus ${hitung(val - 100)}'.trim();
      } else if (val < 1000) {
        return '${satuan[val ~/ 100]} Ratus ${hitung(val % 100)}'.trim();
      } else if (val < 2000) {
        return 'Seribu ${hitung(val - 1000)}'.trim();
      } else if (val < 1000000) {
        return '${hitung(val ~/ 1000)} Ribu ${hitung(val % 1000)}'.trim();
      } else if (val < 1000000000) {
        return '${hitung(val ~/ 1000000)} Juta ${hitung(val % 1000000)}'.trim();
      } else {
        return '${hitung(val ~/ 1000000000)} Miliar ${hitung(val % 1000000000)}'.trim();
      }
    }

    return '${hitung(n)} Rupiah';
  }

  /// Membuat berkas PDF dalam bentuk bytes.
  static Future<Uint8List> generateReceiptPdf({
    required PembayaranItem item,
    required String namaSiswa,
    required String kelas,
    required String nisn,
  }) async {
    final pdf = pw.Document();

    final base = ApiConstants.baseUrl.replaceFirst('/api/v1', '');
    final verifyUrl = '$base/bukti/${item.kode}/${item.tokenVerifikasi ?? ''}';

    pdf.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a5,
        margin: const pw.EdgeInsets.all(24),
        build: (pw.Context context) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              // 1. Kop Kuitansi
              pw.Row(
                crossAxisAlignment: pw.CrossAxisAlignment.center,
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.Text(
                        'E-SPP SEKOLAH',
                        style: pw.TextStyle(
                          fontSize: 16,
                          fontWeight: pw.FontWeight.bold,
                          color: PdfColor.fromHex('#1E3A8A'),
                        ),
                      ),
                      pw.Text(
                        'Sistem Keuangan & Pembayaran Digital',
                        style: const pw.TextStyle(fontSize: 10, color: PdfColors.grey700),
                      ),
                    ],
                  ),
                  pw.Container(
                    padding: const pw.EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: pw.BoxDecoration(
                      color: item.isVoid
                          ? PdfColor.fromHex('#FFF1F2')
                          : PdfColor.fromHex('#ECFDF5'),
                      borderRadius: pw.BorderRadius.circular(6),
                      border: pw.Border.all(
                        color: item.isVoid ? PdfColors.red700 : PdfColors.green700,
                      ),
                    ),
                    child: pw.Text(
                      item.isVoid ? 'VOID / BATAL' : 'LUNAS / SAH',
                      style: pw.TextStyle(
                        fontSize: 11,
                        fontWeight: pw.FontWeight.bold,
                        color: item.isVoid ? PdfColors.red700 : PdfColors.green700,
                      ),
                    ),
                  ),
                ],
              ),
              pw.SizedBox(height: 8),
              pw.Divider(thickness: 1.5, color: PdfColors.grey400),
              pw.SizedBox(height: 12),

              // 2. Data Transaksi & Siswa
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                crossAxisAlignment: pw.CrossAxisAlignment.start,
                children: [
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.Text('DATA SISWA:', style: const pw.TextStyle(fontSize: 9, fontWeight: pw.FontWeight.bold, color: PdfColors.grey700)),
                      pw.SizedBox(height: 2),
                      pw.Text('Nama  : $namaSiswa', style: const pw.TextStyle(fontSize: 11, fontWeight: pw.FontWeight.bold)),
                      pw.Text('NISN  : $nisn', style: const pw.TextStyle(fontSize: 10)),
                      pw.Text('Kelas : $kelas', style: const pw.TextStyle(fontSize: 10)),
                    ],
                  ),
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.end,
                    children: [
                      pw.Text('DETAIL KUITANSI:', style: const pw.TextStyle(fontSize: 9, fontWeight: pw.FontWeight.bold, color: PdfColors.grey700)),
                      pw.SizedBox(height: 2),
                      pw.Text('No: ${item.kode}', style: const pw.TextStyle(fontSize: 11, fontWeight: pw.FontWeight.bold)),
                      pw.Text('Tgl: ${Formatters.tanggal(item.tanggal)}', style: const pw.TextStyle(fontSize: 10)),
                      pw.Text('Metode: ${item.metode}', style: const pw.TextStyle(fontSize: 10)),
                    ],
                  ),
                ],
              ),
              pw.SizedBox(height: 16),

              // 3. Tabel Pembayaran
              pw.Table(
                border: pw.TableBorder.all(color: PdfColors.grey300),
                children: [
                  pw.TableRow(
                    decoration: pw.BoxDecoration(color: PdfColor.fromHex('#F8FAFC')),
                    children: [
                      pw.Padding(
                        padding: const pw.EdgeInsets.all(8),
                        child: pw.Text('Uraian Pembayaran', style: const pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10)),
                      ),
                      pw.Padding(
                        padding: const pw.EdgeInsets.all(8),
                        child: pw.Text('Jumlah', textAlign: pw.TextAlign.right, style: const pw.TextStyle(fontWeight: pw.FontWeight.bold, fontSize: 10)),
                      ),
                    ],
                  ),
                  pw.TableRow(
                    children: [
                      pw.Padding(
                        padding: const pw.EdgeInsets.all(8),
                        child: pw.Column(
                          crossAxisAlignment: pw.CrossAxisAlignment.start,
                          children: [
                            pw.Text('Pembayaran SPP / Biaya Sekolah', style: const pw.TextStyle(fontSize: 10)),
                            pw.Text('Petugas Loket: ${item.petugas}', style: const pw.TextStyle(fontSize: 9, color: PdfColors.grey600)),
                          ],
                        ),
                      ),
                      pw.Padding(
                        padding: const pw.EdgeInsets.all(8),
                        child: pw.Text(
                          Formatters.rupiah(item.jumlah),
                          textAlign: pw.TextAlign.right,
                          style: const pw.TextStyle(fontSize: 11, fontWeight: pw.FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
              pw.SizedBox(height: 10),

              // 4. Terbilang
              pw.Container(
                width: double.infinity,
                padding: const pw.EdgeInsets.all(8),
                decoration: pw.BoxDecoration(
                  color: PdfColor.fromHex('#F1F5F9'),
                  borderRadius: pw.BorderRadius.circular(4),
                ),
                child: pw.Text(
                  'Terbilang: #${terbilang(item.jumlah)}#',
                  style: const pw.TextStyle(fontSize: 9, fontStyle: pw.FontStyle.italic),
                ),
              ),
              pw.Spacer(),

              // 5. QR Code Verifikasi & Tanda Tangan
              pw.Row(
                crossAxisAlignment: pw.CrossAxisAlignment.end,
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.BarcodeWidget(
                        barcode: pw.Barcode.qrCode(),
                        data: verifyUrl,
                        width: 70,
                        height: 70,
                      ),
                      pw.SizedBox(height: 4),
                      pw.Text(
                        'Pindai untuk verifikasi digital',
                        style: const pw.TextStyle(fontSize: 8, color: PdfColors.grey600),
                      ),
                    ],
                  ),
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.center,
                    children: [
                      pw.Text('Bendahara / Kasir Sekolah,', style: const pw.TextStyle(fontSize: 10)),
                      pw.SizedBox(height: 40),
                      pw.Text(
                        item.petugas.isEmpty ? 'Bendahara SPP' : item.petugas,
                        style: const pw.TextStyle(fontSize: 10, fontWeight: pw.FontWeight.bold),
                      ),
                    ],
                  ),
                ],
              ),
            ],
          );
        },
      ),
    );

    return pdf.save();
  }

  /// Menampilkan dialog preview cetak atau kirim PDF.
  static Future<void> printReceipt({
    required PembayaranItem item,
    required String namaSiswa,
    required String kelas,
    required String nisn,
  }) async {
    await Printing.layoutPdf(
      name: 'Kuitansi_${item.kode}.pdf',
      onLayout: (PdfPageFormat format) => generateReceiptPdf(
        item: item,
        namaSiswa: namaSiswa,
        kelas: kelas,
        nisn: nisn,
      ),
    );
  }

  /// Membagikan file PDF kuitansi (bisa ke WhatsApp, Google Drive, email).
  static Future<void> shareReceiptPdf({
    required PembayaranItem item,
    required String namaSiswa,
    required String kelas,
    required String nisn,
  }) async {
    final pdfBytes = await generateReceiptPdf(
      item: item,
      namaSiswa: namaSiswa,
      kelas: kelas,
      nisn: nisn,
    );

    await Printing.sharePdf(
      bytes: pdfBytes,
      filename: 'Kuitansi_${item.kode}.pdf',
    );
  }
}
