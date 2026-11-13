import 'package:dio/dio.dart';

/// Interceptor cerdas untuk simulasi data backend jika `USE_MOCK=true`.
/// Memungkinkan aplikasi dites interaktif tanpa perlu menyalakan backend eksternal.
class MockInterceptor extends Interceptor {
  MockInterceptor({this.enabled = false});

  final bool enabled;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    if (!enabled) {
      return handler.next(options);
    }

    // Delay alami 300ms untuk mensimulasikan jaringan nyata
    await Future<void>.delayed(const Duration(milliseconds: 300));

    final path = options.path;

    if (path.contains('/public/login')) {
      final data = options.data is Map ? options.data as Map : {};
      final username = data['username']?.toString() ?? '';
      final password = data['password']?.toString() ?? '';

      if (password == 'salah') {
        return handler.resolve(
          Response(
            requestOptions: options,
            statusCode: 401,
            data: {
              'success': false,
              'code': 'UNAUTHENTICATED',
              'message': 'NISN atau kata sandi tidak valid.',
              'data': null,
            },
          ),
        );
      }

      final isWali = username.toLowerCase().contains('wali');

      return handler.resolve(
        Response(
          requestOptions: options,
          statusCode: 200,
          data: {
            'success': true,
            'message': isWali
                ? 'Login berhasil sebagai Wali Kelas.'
                : 'Login berhasil.',
            'data': {
              'token': isWali
                  ? 'demo_jwt_token_espp_wali_2026'
                  : 'demo_jwt_token_espp_student_2026',
              'role': isWali ? 'wali_kelas' : 'siswa',
              'name': isWali
                  ? 'Drs. Bambang Sudarsono, M.Pd'
                  : (username.isEmpty ? 'M. Rizky Pratama' : username),
            },
          },
        ),
      );
    }

    if (path.contains('/public/profil-sekolah')) {
      return handler.resolve(
        Response(
          requestOptions: options,
          statusCode: 200,
          data: {
            'success': true,
            'message': 'Profil sekolah berhasil dimuat.',
            'data': {
              'nama_sekolah': 'SMK Negeri Contoh',
              'alamat': 'Jl. Pendidikan No. 1, Sukajadi',
              'no_telp': '(022) 1234567',
              'website': 'www.smknegericontoh.sch.id',
              'npsn': '20219876',
              'kota': 'Bandung',
            },
          },
        ),
      );
    }

    if (path.contains('/siswa/dashboard')) {
      return handler.resolve(
        Response(
          requestOptions: options,
          statusCode: 200,
          data: {
            'success': true,
            'message': 'Data ringkasan keuangan berhasil dimuat.',
            'data': {
              'nama': 'M. Rizky Pratama',
              'nisn': '0065432190',
              'kelas': 'XII Rekayasa Perangkat Lunak 1',
              'tagihan_jatuh_tempo': 250000,
              'total_tagihan': 3000000,
              'total_dibayar': 2750000,
            },
          },
        ),
      );
    }

    if (path.contains('/siswa/kartu-spp')) {
      return handler.resolve(
        Response(
          requestOptions: options,
          statusCode: 200,
          data: {
            'success': true,
            'message': 'Kartu SPP berhasil dimuat.',
            'data': {
              'spp': [
                {'bulan': 'Juli', 'tahun': 2026, 'nominal': 250000, 'dibayar': 250000, 'status': 'lunas', 'no_kuitansi': 'SPP-20260715-0012'},
                {'bulan': 'Agustus', 'tahun': 2026, 'nominal': 250000, 'dibayar': 250000, 'status': 'lunas', 'no_kuitansi': 'SPP-20260814-0045'},
                {'bulan': 'September', 'tahun': 2026, 'nominal': 250000, 'dibayar': 250000, 'status': 'lunas', 'no_kuitansi': 'SPP-20260910-0088'},
                {'bulan': 'Oktober', 'tahun': 2026, 'nominal': 250000, 'dibayar': 250000, 'status': 'lunas', 'no_kuitansi': 'SPP-20261012-0105'},
                {'bulan': 'November', 'tahun': 2026, 'nominal': 250000, 'dibayar': 0, 'status': 'belum_lunas', 'no_kuitansi': null},
                {'bulan': 'Desember', 'tahun': 2026, 'nominal': 250000, 'dibayar': 0, 'status': 'akan_datang', 'no_kuitansi': null},
                {'bulan': 'Januari', 'tahun': 2027, 'nominal': 250000, 'dibayar': 0, 'status': 'akan_datang', 'no_kuitansi': null},
                {'bulan': 'Februari', 'tahun': 2027, 'nominal': 250000, 'dibayar': 0, 'status': 'akan_datang', 'no_kuitansi': null},
                {'bulan': 'Maret', 'tahun': 2027, 'nominal': 250000, 'dibayar': 0, 'status': 'akan_datang', 'no_kuitansi': null},
                {'bulan': 'April', 'tahun': 2027, 'nominal': 250000, 'dibayar': 0, 'status': 'akan_datang', 'no_kuitansi': null},
                {'bulan': 'Mei', 'tahun': 2027, 'nominal': 250000, 'dibayar': 0, 'status': 'akan_datang', 'no_kuitansi': null},
                {'bulan': 'Juni', 'tahun': 2027, 'nominal': 250000, 'dibayar': 0, 'status': 'ditanggung_pemerintah', 'no_kuitansi': null},
              ],
              'non_spp': [
                {'nama_pos': 'Uang Gedung / Sarana', 'nominal': 1500000, 'dibayar': 1500000, 'status': 'lunas'},
                {'nama_pos': 'Paket Seragam & Atribut', 'nominal': 600000, 'dibayar': 400000, 'status': 'sebagian'},
                {'nama_pos': 'Uji Kompetensi Kejuruan', 'nominal': 350000, 'dibayar': 0, 'status': 'belum_lunas'},
              ],
              'pembayaran': [
                {
                  'kode_transaksi': 'SPP-20261012-0105',
                  'tgl_bayar': '2026-10-12T09:30:00Z',
                  'created_at': '2026-10-12T09:30:12Z',
                  'jumlah_bayar': 250000,
                  'metode': 'Transfer Bank (BCA)',
                  'petugas': 'Bendahara Sekolah',
                  'status': 'valid',
                  'token': 'a8f4c2e1b9',
                  'nis': '240101',
                  'tahun_ajaran': '2026/2027',
                  'rincian': [
                    {
                      'keterangan': 'SPP Bulan Oktober 2026',
                      'tahun_ajaran': '2026/2027',
                      'jumlah': 250000,
                    },
                  ],
                },
                {
                  'kode_transaksi': 'SPP-20260910-0088',
                  'tgl_bayar': '2026-09-10T08:15:00Z',
                  'created_at': '2026-09-10T08:15:41Z',
                  'jumlah_bayar': 250000,
                  'metode': 'Tunai di Loket',
                  'petugas': 'Siti Rahmawati, S.Pd',
                  'status': 'valid',
                  'token': '3d5e7f1a2c',
                  'nis': '240101',
                  'tahun_ajaran': '2026/2027',
                  'rincian': [
                    {
                      'keterangan': 'SPP Bulan September 2026',
                      'tahun_ajaran': '2026/2027',
                      'jumlah': 250000,
                    },
                  ],
                },
                {
                  'kode_transaksi': 'SRG-20260814-0045',
                  'tgl_bayar': '2026-08-14T11:00:00Z',
                  'created_at': '2026-08-14T11:00:27Z',
                  'jumlah_bayar': 400000,
                  'metode': 'QRIS',
                  'petugas': 'Loket Kasir 1',
                  'status': 'valid',
                  'token': 'f7e9b1c3a5',
                  'nis': '240101',
                  'tahun_ajaran': '2026/2027',
                  'rincian': [
                    {
                      'keterangan': 'Paket Seragam & Atribut',
                      'tahun_ajaran': '2026/2027',
                      'jumlah': 400000,
                      'subsidi_persen': 10,
                    },
                  ],
                },
              ],
            },
          },
        ),
      );
    }

    if (path.contains('/wali/tunggakan')) {
      return handler.resolve(
        Response(
          requestOptions: options,
          statusCode: 200,
          data: {
            'success': true,
            'message': 'Data tunggakan kelas berhasil dimuat.',
            'data': {
              'total_menunggak': 3,
              'siswa': [
                {
                  'id': 101,
                  'nisn': '0065432190',
                  'nis': '240101',
                  'nama': 'Ahmad Fauzi',
                  'no_hp_wali': '081234567890',
                  'total_tunggakan': 500000,
                  'bulan_menunggak': ['Oktober', 'November'],
                  'status_bulan_ini': 'belum_lunas',
                },
                {
                  'id': 102,
                  'nisn': '0065432191',
                  'nis': '240102',
                  'nama': 'Bintang Pratama',
                  'no_hp_wali': '082198765432',
                  'total_tunggakan': 250000,
                  'bulan_menunggak': ['November'],
                  'status_bulan_ini': 'belum_lunas',
                },
                {
                  'id': 105,
                  'nisn': '0065432194',
                  'nis': '240105',
                  'nama': 'Eka Putri Rahayu',
                  'no_hp_wali': '085611223344',
                  'total_tunggakan': 750000,
                  'bulan_menunggak': ['September', 'Oktober', 'November'],
                  'status_bulan_ini': 'belum_lunas',
                },
              ],
            },
          },
        ),
      );
    }

    if (path.contains('/wali/rekap-kelas')) {
      return handler.resolve(
        Response(
          requestOptions: options,
          statusCode: 200,
          data: {
            'success': true,
            'message': 'Rekap kelas berhasil dimuat.',
            'data': {
              'kelas_id': '1',
              'nama_kelas': 'XII Rekayasa Perangkat Lunak 1',
              'wali_kelas': 'Drs. Bambang Sudarsono, M.Pd',
              'total_siswa': 32,
              'siswa_lunas': 26,
              'siswa_menunggak': 6,
              'total_tunggakan': 2250000,
              'siswa': [
                {
                  'id': 101,
                  'nisn': '0065432190',
                  'nis': '240101',
                  'nama': 'Ahmad Fauzi',
                  'no_hp_wali': '081234567890',
                  'total_tunggakan': 500000,
                  'bulan_menunggak': ['Oktober', 'November'],
                  'status_bulan_ini': 'belum_lunas',
                },
                {
                  'id': 102,
                  'nisn': '0065432191',
                  'nis': '240102',
                  'nama': 'Bintang Pratama',
                  'no_hp_wali': '082198765432',
                  'total_tunggakan': 250000,
                  'bulan_menunggak': ['November'],
                  'status_bulan_ini': 'belum_lunas',
                },
                {
                  'id': 103,
                  'nisn': '0065432192',
                  'nis': '240103',
                  'nama': 'Citra Lestari',
                  'no_hp_wali': '085712345678',
                  'total_tunggakan': 0,
                  'bulan_menunggak': [],
                  'status_bulan_ini': 'lunas',
                },
                {
                  'id': 104,
                  'nisn': '0065432193',
                  'nis': '240104',
                  'nama': 'Dedi Supriadi',
                  'no_hp_wali': '081399887766',
                  'total_tunggakan': 0,
                  'bulan_menunggak': [],
                  'status_bulan_ini': 'lunas',
                },
                {
                  'id': 105,
                  'nisn': '0065432194',
                  'nis': '240105',
                  'nama': 'Eka Putri Rahayu',
                  'no_hp_wali': '085611223344',
                  'total_tunggakan': 750000,
                  'bulan_menunggak': ['September', 'Oktober', 'November'],
                  'status_bulan_ini': 'belum_lunas',
                },
              ],
            },
          },
        ),
      );
    }

    return handler.next(options);
  }
}
