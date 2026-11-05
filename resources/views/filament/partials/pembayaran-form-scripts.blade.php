@if (request()->routeIs('filament.admin.resources.pembayarans.create'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let buttonAttempts = 0;

            const focusStudentSearch = function () {
                const button = document.getElementById('form.siswa_id');

                if (!button || button.disabled) {
                    if (buttonAttempts++ < 20) {
                        window.setTimeout(focusStudentSearch, 100);
                    }

                    return;
                }

                button.click();

                let searchAttempts = 0;

                const focusSearchInput = function () {
                    const search = document.querySelector('.fi-select-input-search-ctn input[role="combobox"]');

                    if (search) {
                        search.focus();

                        return;
                    }

                    if (searchAttempts++ < 10) {
                        window.setTimeout(focusSearchInput, 100);
                    }
                };

                window.setTimeout(focusSearchInput, 100);
            };

            window.setTimeout(focusStudentSearch, 250);
        });
    </script>
@endif
