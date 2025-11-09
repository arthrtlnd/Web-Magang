// Format NIK (hanya angka)
        document.getElementById('nik').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Format No HP (hanya angka)
        document.getElementById('no_hp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Format No KEP (hanya angka)
        document.getElementById('no_kep').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Format No Sprint (hanya angka)
        document.getElementById('no_sprint').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
        
        // Filter Korp berdasarkan Matra
        const korpByMatra = {
            '1': ['A1', 'B1', 'C1', 'D1', 'E1', 'F1', 'G1', 'H1', 'K1', 'M1', 'N1', 'P1', 'Q1', 'R1', 'X1', 'Y1', 'Z1', 'A3'], // TNI AD
            '2': ['12', '22', '32', '42', '52', '62', '72', '82'], // TNI AL
            '3': ['13', '23', '33', '43', '53', '63', '73', '83', '93', 'A3'], // TNI AU
            '0': [] // PNS - tidak punya korp
        };
        
        const matraSelect = document.getElementById('matra');
        const korpSelect = document.getElementById('korp');
        const allKorpOptions = Array.from(korpSelect.options);
        
        function filterKorp() {
            const selectedMatra = matraSelect.value;
            const currentKorp = korpSelect.value;
            
            // Hapus semua option kecuali yang pertama
            korpSelect.innerHTML = '<option value="">-- Pilih Korp --</option>';
            
            if (selectedMatra === '0') {
                // Jika PNS, disable dropdown korp
                korpSelect.disabled = true;
                korpSelect.value = '';
            } else if (selectedMatra && korpByMatra[selectedMatra]) {
                // Enable dropdown
                korpSelect.disabled = false;
                
                // Tambahkan option yang sesuai dengan matra
                allKorpOptions.forEach(option => {
                    if (option.value && korpByMatra[selectedMatra].includes(option.value)) {
                        const newOption = option.cloneNode(true);
                        if (option.value === currentKorp) {
                            newOption.selected = true;
                        }
                        korpSelect.appendChild(newOption);
                    }
                });
            } else {
                // Jika matra tidak dipilih, enable dan tampilkan semua korp
                korpSelect.disabled = false;
                allKorpOptions.forEach(option => {
                    if (option.value) {
                        const newOption = option.cloneNode(true);
                        if (option.value === currentKorp) {
                            newOption.selected = true;
                        }
                        korpSelect.appendChild(newOption);
                    }
                });
            }
        }
        
        // Event listener untuk matra
        matraSelect.addEventListener('change', filterKorp);
        
        // Filter saat halaman pertama kali dimuat
        filterKorp();