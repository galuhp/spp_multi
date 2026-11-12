import 'package:intl/intl.dart';

class Formatters {
  Formatters._();

  static final _rupiah =
      NumberFormat.currency(locale: 'id_ID', symbol: 'Rp ', decimalDigits: 0);

  static String rupiah(num value) => _rupiah.format(value);

  static String tanggal(DateTime date) =>
      DateFormat('d MMMM y', 'id_ID').format(date);
}
