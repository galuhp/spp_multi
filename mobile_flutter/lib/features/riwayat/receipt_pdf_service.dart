import 'dart:typed_data';

import 'package:flutter/services.dart' show rootBundle;
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:printing/printing.dart';

import '../../core/constants/api_constants.dart';
import '../../core/models/school_profile.dart';
import '../../core/utils/formatters.dart';
import '../tagihan/tagihan_models.dart';

/// Service pembuat dokumen PDF resmi kuitansi pembayaran SPP.
///
/// Layout mengikuti kuitansi Laravel (`resources/views/pembayaran/partials/
/// kuitansi.blade.php`): kop sekolah, data siswa, tabel rincian, QR verifikasi,
/// dan tiga kolom tanda tangan.
class ReceiptPdfService {
  ReceiptPdfService._();

  static final _zinc900 = PdfColor.fromHex('#18181B');
  static final _zinc700 = PdfColor.fromHex('#3F3F46');
  static final _zinc600 = PdfColor.fromHex('#52525B');
  static final _zinc500 = PdfColor.fromHex('#71717A');
  static final _zinc300 = PdfColor.fromHex('#D4D4D8');
  static final _zinc200 = PdfColor.fromHex('#E4E4E7');
  static final _zinc100 = PdfColor.fromHex('#F4F4F5');
  static final _roseBg = PdfColor.fromHex('#FFF1F2');
  static final _roseBorder = PdfColor.fromHex('#FDA4AF');
  static final _roseText = PdfColor.fromHex('#9F1239');

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
        return '${hitung(val ~/ 1000000000)} Miliar ${hitung(val % 1000000000)}'
            .trim();
      }
    }

    return '${hitung(n)} Rupiah';
  }

  /// Font lokal IBM Plex Sans agar kuitansi mendukung karakter Unicode
  /// (font bawaan PDF hanya Latin-1). Fallback ke font bawaan jika aset gagal
  /// dimuat, mis. pada lingkungan pengujian tanpa bundle aset.
  static Future<pw.ThemeData> _pdfTheme() async {
    try {
      final data =
          await rootBundle.load('assets/fonts/IBMPlexSans-Variable.ttf');
      final font = pw.Font.ttf(data);
      return pw.ThemeData.withFont(
        base: font,
        bold: font,
        italic: font,
        boldItalic: font,
      );
    } catch (_) {
      return pw.ThemeData.base();
    }
  }

  static pw.TextStyle _style({
    double size = 9,
    PdfColor? color,
    bool bold = false,
    bool italic = false,
    bool underline = false,
    double? spacing,
  }) =>
      pw.TextStyle(
        fontSize: size,
        color: color ?? _zinc900,
        fontWeight: bold ? pw.FontWeight.bold : pw.FontWeight.normal,
        fontStyle: italic ? pw.FontStyle.italic : pw.FontStyle.normal,
        decoration: underline ? pw.TextDecoration.underline : null,
        letterSpacing: spacing,
      );

  static String _dua(int value) => value.toString().padLeft(2, '0');

  static String _jam(DateTime time) =>
      '${_dua(time.hour)}:${_dua(time.minute)}:${_dua(time.second)} WIB';

  /// Membuat berkas PDF dalam bentuk bytes.
  static Future<Uint8List> generateReceiptPdf({
    required PembayaranItem item,
    required String namaSiswa,
    required String kelas,
    required String nisn,
    SchoolProfile sekolah = SchoolProfile.fallback,
  }) async {
    final theme = await _pdfTheme();
    final pdf = pw.Document();

    final base = ApiConstants.baseUrl.replaceFirst('/api/v1', '');
    final verifyUrl = '$base/bukti/${item.kode}/${item.tokenVerifikasi ?? ''}';
    final rincian = item.rincianEfektif;
    final total = rincian.fold<num>(0, (sum, row) => sum + row.jumlah);
    final waktu = item.waktu ?? item.tanggal;
    final adaSubsidi = rincian.any((row) => row.subsidiPersen != null);
    final tanggalBayar = Formatters.tanggal(item.tanggal);

    pdf.addPage(
      pw.Page(
        theme: theme,
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.all(32),
        build: (pw.Context context) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.stretch,
            children: [
              // 1. Kop sekolah + nomor kuitansi.
              pw.Container(
                padding: const pw.EdgeInsets.only(bottom: 8),
                decoration: pw.BoxDecoration(
                  border: pw.Border(
                    bottom: pw.BorderSide(width: 2, color: _zinc900),
                  ),
                ),
                child: pw.Row(
                  crossAxisAlignment: pw.CrossAxisAlignment.start,
                  children: [
                    pw.Container(
                      width: 42,
                      height: 42,
                      alignment: pw.Alignment.center,
                      decoration: pw.BoxDecoration(
                        color: _zinc900,
                        borderRadius: pw.BorderRadius.circular(6),
                      ),
                      child: pw.Text(
                        sekolah.inisial,
                        style: _style(
                            size: 18, color: PdfColors.white, bold: true),
                      ),
                    ),
                    pw.SizedBox(width: 10),
                    pw.Expanded(
                      child: pw.Column(
                        crossAxisAlignment: pw.CrossAxisAlignment.start,
                        children: [
                          pw.Text(
                            sekolah.nama.toUpperCase(),
                            style: _style(size: 12, bold: true, spacing: 0.3),
                          ),
                          pw.SizedBox(height: 2),
                          pw.Text(
                            '${sekolah.alamat} • Telp. ${sekolah.telepon}',
                            style: _style(size: 8, color: _zinc600),
                          ),
                          pw.Text(
                            'Website: ${sekolah.website} • NPSN: ${sekolah.npsn}',
                            style: _style(size: 7.5, color: _zinc500),
                          ),
                        ],
                      ),
                    ),
                    pw.SizedBox(width: 12),
                    pw.Column(
                      crossAxisAlignment: pw.CrossAxisAlignment.end,
                      children: [
                        pw.Text(
                          'KUITANSI PEMBAYARAN SPP',
                          style: _style(
                            size: 7.5,
                            color: _zinc500,
                            bold: true,
                            spacing: 0.8,
                          ),
                        ),
                        pw.SizedBox(height: 2),
                        pw.Text(
                          item.kode,
                          style: _style(size: 10, bold: true),
                        ),
                      ],
                    ),
                  ],
                ),
              ),

              // 2. Banner transaksi dibatalkan.
              if (item.isVoid) ...[
                pw.SizedBox(height: 10),
                pw.Container(
                  padding: const pw.EdgeInsets.all(8),
                  decoration: pw.BoxDecoration(
                    color: _roseBg,
                    borderRadius: pw.BorderRadius.circular(6),
                    border: pw.Border.all(color: _roseBorder),
                  ),
                  child: pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.start,
                    children: [
                      pw.Text(
                        'TRANSAKSI DIBATALKAN',
                        style: _style(size: 8, color: _roseText, bold: true),
                      ),
                      if (item.voidAlasan != null)
                        pw.Text(
                          'Alasan: ${item.voidAlasan}',
                          style: _style(size: 8, color: _roseText),
                        ),
                    ],
                  ),
                ),
              ],

              // 3. Data siswa & detail transaksi.
              pw.Container(
                padding: const pw.EdgeInsets.symmetric(vertical: 10),
                decoration: pw.BoxDecoration(
                  border: pw.Border(
                    bottom: pw.BorderSide(width: 1, color: _zinc200),
                  ),
                ),
                child: pw.Row(
                  crossAxisAlignment: pw.CrossAxisAlignment.start,
                  children: [
                    pw.Expanded(
                      child: pw.Column(
                        crossAxisAlignment: pw.CrossAxisAlignment.start,
                        children: [
                          _infoRow('Nama Siswa', namaSiswa, bold: true),
                          _infoRow('NISN / NIS', '$nisn / ${item.nis ?? '-'}'),
                          _infoRow('Kelas / Jurusan', kelas),
                        ],
                      ),
                    ),
                    pw.SizedBox(width: 16),
                    pw.Expanded(
                      child: pw.Column(
                        crossAxisAlignment: pw.CrossAxisAlignment.end,
                        children: [
                          _infoRow('Tanggal Bayar', tanggalBayar,
                              bold: true, alignRight: true),
                          _infoRow('Waktu Transaksi', _jam(waktu),
                              alignRight: true),
                          _infoRow('Metode', item.metode, alignRight: true),
                          _infoRow('Petugas Kasir', item.petugas,
                              alignRight: true),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              // 4. Tabel rincian pembayaran.
              pw.SizedBox(height: 10),
              pw.Table(
                columnWidths: {
                  0: const pw.FixedColumnWidth(24),
                  1: const pw.FlexColumnWidth(),
                  2: const pw.FixedColumnWidth(100),
                  3: const pw.FixedColumnWidth(84),
                },
                children: [
                  pw.TableRow(
                    decoration: pw.BoxDecoration(
                      color: _zinc100,
                      border: pw.Border(
                        bottom: pw.BorderSide(width: 1, color: _zinc300),
                      ),
                    ),
                    children: [
                      _cell('No', bold: true, center: true),
                      _cell('Keterangan / Pos Pembayaran', bold: true),
                      _cell('Tahun Ajaran', bold: true, center: true),
                      _cell('Jumlah Bayar', bold: true, alignRight: true),
                    ],
                  ),
                  for (var i = 0; i < rincian.length; i++)
                    pw.TableRow(
                      decoration: pw.BoxDecoration(
                        border: pw.Border(
                          bottom: pw.BorderSide(width: 0.5, color: _zinc200),
                        ),
                      ),
                      children: [
                        _cell('${i + 1}', center: true),
                        pw.Padding(
                          padding: const pw.EdgeInsets.all(4),
                          child: pw.Column(
                            crossAxisAlignment: pw.CrossAxisAlignment.start,
                            children: [
                              pw.Text(
                                rincian[i].keterangan,
                                style: _style(size: 8.5, bold: true),
                              ),
                              if (rincian[i].subsidiPersen != null)
                                pw.Text(
                                  '* Potongan subsidi/diskon ${rincian[i].subsidiPersen}%',
                                  style: _style(
                                    size: 7,
                                    color: _zinc500,
                                    italic: true,
                                  ),
                                ),
                            ],
                          ),
                        ),
                        _cell(rincian[i].tahunAjaran ?? '-', center: true),
                        _cell(Formatters.rupiah(rincian[i].jumlah),
                            alignRight: true),
                      ],
                    ),
                  pw.TableRow(
                    decoration: pw.BoxDecoration(
                      border: pw.Border(
                        top: pw.BorderSide(width: 2, color: _zinc900),
                      ),
                    ),
                    children: [
                      pw.SizedBox(),
                      pw.SizedBox(),
                      _cell('TOTAL PENERIMAAN:',
                          bold: true, alignRight: true, size: 7.5),
                      _cell(Formatters.rupiah(total),
                          bold: true, alignRight: true, size: 10),
                    ],
                  ),
                ],
              ),
              if (adaSubsidi) ...[
                pw.SizedBox(height: 4),
                pw.Text(
                  '* Catatan: Nominal pembayaran telah memperhitungkan potongan subsidi/diskon siswa.',
                  style: _style(size: 7, color: _zinc500, italic: true),
                ),
              ],

              // 5. QR verifikasi + tanda tangan.
              pw.Container(
                padding: const pw.EdgeInsets.only(top: 16),
                decoration: pw.BoxDecoration(
                  border: pw.Border(
                    top: pw.BorderSide(width: 0.5, color: _zinc200),
                  ),
                ),
                child: pw.Row(
                  crossAxisAlignment: pw.CrossAxisAlignment.end,
                  children: [
                    pw.Expanded(
                      child: _signature('Siswa / Orang Tua,', namaSiswa),
                    ),
                    pw.Expanded(
                      child: pw.Column(
                        crossAxisAlignment: pw.CrossAxisAlignment.center,
                        children: [
                          pw.Container(
                            padding: const pw.EdgeInsets.all(4),
                            decoration: pw.BoxDecoration(
                              color: PdfColors.white,
                              borderRadius: pw.BorderRadius.circular(4),
                              border: pw.Border.all(color: _zinc300),
                            ),
                            child: pw.BarcodeWidget(
                              barcode: pw.Barcode.qrCode(),
                              data: verifyUrl,
                              width: 74,
                              height: 74,
                              // QR tidak butuh teks di bawahnya; menghindari
                              // font Courier bawaan yang tidak mendukung Unicode.
                              drawText: false,
                            ),
                          ),
                          pw.SizedBox(height: 4),
                          pw.Text(
                            'SCAN CEK BUKTI ASLI',
                            style: _style(
                              size: 7,
                              color: _zinc700,
                              bold: true,
                              spacing: 0.4,
                            ),
                          ),
                          pw.Text(
                            'Verifikasi online orang tua/wali',
                            style: _style(size: 6.5, color: _zinc500),
                          ),
                        ],
                      ),
                    ),
                    pw.Expanded(
                      child: _signature(
                        '${sekolah.kota}, $tanggalBayar\nPetugas Kasir,',
                        item.petugas.isEmpty ? 'Bendahara SPP' : item.petugas,
                      ),
                    ),
                  ],
                ),
              ),

              // 6. Catatan keabsahan.
              pw.Container(
                margin: const pw.EdgeInsets.only(top: 16),
                padding: const pw.EdgeInsets.only(top: 8),
                decoration: pw.BoxDecoration(
                  border: pw.Border(
                    top: pw.BorderSide(width: 0.5, color: _zinc200),
                  ),
                ),
                child: pw.Text(
                  '* Kuitansi ini sah diterbitkan oleh sistem sekolah pada '
                  '${Formatters.tanggal(waktu)} ${_jam(waktu)}. '
                  'Orang tua dapat memindai (scan) QR Code di atas menggunakan '
                  'kamera HP untuk melihat bukti pembayaran digital resmi.',
                  textAlign: pw.TextAlign.center,
                  style: _style(size: 6.5, color: _zinc500),
                ),
              ),
            ],
          );
        },
      ),
    );

    return pdf.save();
  }

  static pw.Widget _infoRow(
    String label,
    String value, {
    bool bold = false,
    bool alignRight = false,
  }) {
    return pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 2),
      child: pw.Row(
        mainAxisAlignment:
            alignRight ? pw.MainAxisAlignment.end : pw.MainAxisAlignment.start,
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Text('$label: ', style: _style(size: 8, color: _zinc500)),
          if (alignRight)
            pw.Text(value, style: _style(size: 8.5, bold: bold))
          else
            pw.Expanded(
              child: pw.Text(
                value,
                style: _style(size: 8.5, bold: bold),
              ),
            ),
        ],
      ),
    );
  }

  static pw.Widget _signature(String title, String name) => pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.center,
        children: [
          pw.Text(
            title,
            textAlign: pw.TextAlign.center,
            style: _style(size: 8, color: _zinc600),
          ),
          pw.SizedBox(height: 28),
          pw.Text(
            name,
            textAlign: pw.TextAlign.center,
            style: _style(size: 9, bold: true, underline: true),
          ),
        ],
      );

  static pw.Widget _cell(
    String text, {
    bool bold = false,
    bool center = false,
    bool alignRight = false,
    double size = 8,
  }) =>
      pw.Padding(
        padding: const pw.EdgeInsets.all(4),
        child: pw.Text(
          text,
          textAlign: alignRight
              ? pw.TextAlign.right
              : (center ? pw.TextAlign.center : pw.TextAlign.left),
          style: _style(
            size: size,
            bold: bold,
            color: bold ? _zinc900 : _zinc700,
          ),
        ),
      );

  /// Menampilkan dialog preview cetak atau kirim PDF.
  static Future<void> printReceipt({
    required PembayaranItem item,
    required String namaSiswa,
    required String kelas,
    required String nisn,
    SchoolProfile sekolah = SchoolProfile.fallback,
  }) async {
    await Printing.layoutPdf(
      name: 'Kuitansi_${item.kode}.pdf',
      onLayout: (PdfPageFormat format) => generateReceiptPdf(
        item: item,
        namaSiswa: namaSiswa,
        kelas: kelas,
        nisn: nisn,
        sekolah: sekolah,
      ),
    );
  }

  /// Membagikan file PDF kuitansi (bisa ke WhatsApp, Google Drive, email).
  static Future<void> shareReceiptPdf({
    required PembayaranItem item,
    required String namaSiswa,
    required String kelas,
    required String nisn,
    SchoolProfile sekolah = SchoolProfile.fallback,
  }) async {
    final pdfBytes = await generateReceiptPdf(
      item: item,
      namaSiswa: namaSiswa,
      kelas: kelas,
      nisn: nisn,
      sekolah: sekolah,
    );

    await Printing.sharePdf(
      bytes: pdfBytes,
      filename: 'Kuitansi_${item.kode}.pdf',
    );
  }
}
