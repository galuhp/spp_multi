<script>
    document.addEventListener('livewire:init', function () {
        // Livewire melaporkan request dari halaman yang kedaluwarsa (misalnya tab yang
        // dibuka sebelum deploy baru) sebagai HTTP 419. Muat ulang halaman sekali agar
        // pengguna tidak melihat notifikasi "Error while loading page".
        const kunciMuatUlang = 'livewire-419-muat-ulang-terakhir';
        const jedaMuatUlang = 10000;
        let sedangMemuatUlang = false;

        const bacaWaktuMuatUlangTerakhir = function () {
            try {
                return Number(window.sessionStorage.getItem(kunciMuatUlang) ?? 0);
            } catch (error) {
                return 0;
            }
        };

        const simpanWaktuMuatUlang = function () {
            try {
                window.sessionStorage.setItem(kunciMuatUlang, String(Date.now()));
            } catch (error) {
                // Abaikan bila sessionStorage tidak tersedia (mis. mode privat).
            }
        };

        Livewire.interceptRequest(function ({ onError }) {
            onError(function ({ response, preventDefault }) {
                if (response?.status !== 419 || sedangMemuatUlang) {
                    return;
                }

                if (Date.now() - bacaWaktuMuatUlangTerakhir() < jedaMuatUlang) {
                    // Biarkan Livewire menampilkan konfirmasi bawaan agar tidak terjadi
                    // muat ulang berulang saat sesi memang sudah benar-benar berakhir.
                    return;
                }

                preventDefault();
                sedangMemuatUlang = true;
                simpanWaktuMuatUlang();
                window.location.reload();
            });
        });
    });
</script>
