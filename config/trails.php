<?php

/*
|--------------------------------------------------------------------------
| Peta Gunung — Bank Data Jalur Pendakian
|--------------------------------------------------------------------------
|
| Sumber: Bank Data Jalur Pendakian Gunung milik PMPA Astacala Universitas
| Telkom (https://astacala.org/bank-data), diinisiasi oleh Wahyu Djatmiko
| (A-030-H). Berkas GPX/KMZ/PDF tidak disalin ke server ini; tautan unduhan
| langsung mengarah ke server Astacala. Koordinat "lat"/"lng" adalah titik
| tengah track GPX tiap jalur, hanya dipakai untuk menaruh penanda di peta.
| "photo" merujuk ke slug di config/mountains.php bila fotonya tersedia.
|
*/

return [

    'source' => [
        'name' => 'PMPA Astacala Universitas Telkom',
        'url' => 'https://astacala.org/bank-data',
        'files_base_url' => 'https://astacala.org/api_astacala/uploads/images/jalur-pendakian',
        'curator' => 'Wahyu Djatmiko (A-030-H)',
    ],

    'regions' => ['Jawa Barat', 'Jawa Tengah', 'Jawa Timur', 'Bali', 'Nusa Tenggara Barat', 'Nusa Tenggara Timur', 'Sumatera', 'Sulawesi', 'Maluku', 'Kalimantan'],

    'trails' => [
        ['name' => 'Bukit Tunggul', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'bukit-tunggul', 'kmz' => true, 'pdf' => true, 'lat' => -6.81631, 'lng' => 107.71838, 'photo' => null],
        ['name' => 'Burangrang', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'burangrang', 'kmz' => true, 'pdf' => false, 'lat' => -6.77895, 'lng' => 107.5634, 'photo' => null],
        ['name' => 'Ceremai', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'ceremai', 'kmz' => true, 'pdf' => false, 'lat' => -6.90897, 'lng' => 108.40123, 'photo' => 'ciremai'],
        ['name' => 'Cikuray', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'cikuray', 'kmz' => true, 'pdf' => false, 'lat' => -7.32433, 'lng' => 107.85189, 'photo' => 'cikuray'],
        ['name' => 'Galunggung', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'galunggung', 'kmz' => true, 'pdf' => false, 'lat' => -7.24264, 'lng' => 108.05294, 'photo' => null],
        ['name' => 'Gede - Pangrango', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'gede-pangrango', 'kmz' => true, 'pdf' => true, 'lat' => -6.79283, 'lng' => 106.98012, 'photo' => 'gede-pangrango'],
        ['name' => 'Guntur - Masigit', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'guntur-masigit', 'kmz' => true, 'pdf' => false, 'lat' => -7.15816, 'lng' => 107.84291, 'photo' => 'guntur'],
        ['name' => 'Halimun', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'halimun', 'kmz' => true, 'pdf' => false, 'lat' => -6.78718, 'lng' => 106.51683, 'photo' => null],
        ['name' => 'Kendeng', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'kendeng', 'kmz' => true, 'pdf' => false, 'lat' => -7.25637, 'lng' => 107.69311, 'photo' => null],
        ['name' => 'Manglayang', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'manglayang', 'kmz' => true, 'pdf' => false, 'lat' => -6.88368, 'lng' => 107.74118, 'photo' => null],
        ['name' => 'Papandayan', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'papandayan', 'kmz' => true, 'pdf' => false, 'lat' => -7.31521, 'lng' => 107.72858, 'photo' => 'papandayan'],
        ['name' => 'Patuha', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'patuha', 'kmz' => true, 'pdf' => false, 'lat' => -7.16588, 'lng' => 107.39838, 'photo' => null],
        ['name' => 'Rakutak', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'rakutak', 'kmz' => true, 'pdf' => false, 'lat' => -7.13922, 'lng' => 107.71332, 'photo' => null],
        ['name' => 'Salak', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'salak', 'kmz' => true, 'pdf' => false, 'lat' => -6.71934, 'lng' => 106.73426, 'photo' => 'salak'],
        ['name' => 'Tangkuban Perahu', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'tangkuban-perahu', 'kmz' => true, 'pdf' => true, 'lat' => -6.77283, 'lng' => 107.61661, 'photo' => null],
        ['name' => 'Wayang', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'wayang', 'kmz' => true, 'pdf' => false, 'lat' => -7.20731, 'lng' => 107.63501, 'photo' => null],
        ['name' => 'Peg. Malabar Puncak Besar', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'peg-malabar-puncak-besar', 'kmz' => true, 'pdf' => false, 'lat' => -7.16394, 'lng' => 107.61331, 'photo' => null],
        ['name' => 'Peg. Malabar Puncak Haruman', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'peg-malabar-puncak-haruman', 'kmz' => true, 'pdf' => false, 'lat' => -7.12244, 'lng' => 107.60664, 'photo' => null],
        ['name' => 'Peg. Malabar Puncak Puntang', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'peg-malabar-puncak-puntang', 'kmz' => true, 'pdf' => false, 'lat' => -7.11553, 'lng' => 107.61278, 'photo' => null],
        ['name' => 'Tegal Panjang', 'area' => 'Jawa Barat', 'region' => 'Jawa Barat', 'file' => 'tegal-panjang', 'kmz' => true, 'pdf' => true, 'lat' => -7.28464, 'lng' => 107.71928, 'photo' => null],
        ['name' => 'Andong', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'andong', 'kmz' => true, 'pdf' => true, 'lat' => -7.39153, 'lng' => 110.37522, 'photo' => null],
        ['name' => 'Bismo', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'bismo', 'kmz' => true, 'pdf' => true, 'lat' => -7.24115, 'lng' => 109.89012, 'photo' => null],
        ['name' => 'Kembang', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'kembang', 'kmz' => true, 'pdf' => true, 'lat' => -7.32025, 'lng' => 109.97147, 'photo' => null],
        ['name' => 'Lawu', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'lawu', 'kmz' => true, 'pdf' => true, 'lat' => -7.62131, 'lng' => 111.19903, 'photo' => 'lawu'],
        ['name' => 'Merapi', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'merapi', 'kmz' => true, 'pdf' => true, 'lat' => -7.5288, 'lng' => 110.44411, 'photo' => 'merapi'],
        ['name' => 'Merbabu', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'merbabu', 'kmz' => true, 'pdf' => true, 'lat' => -7.448, 'lng' => 110.42731, 'photo' => 'merbabu'],
        ['name' => 'Prau', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'prau', 'kmz' => true, 'pdf' => true, 'lat' => -7.2006, 'lng' => 109.93032, 'photo' => 'prau'],
        ['name' => 'Sindoro', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'sindoro', 'kmz' => true, 'pdf' => false, 'lat' => -7.30797, 'lng' => 110.00537, 'photo' => 'sindoro'],
        ['name' => 'Slamet', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'slamet', 'kmz' => true, 'pdf' => true, 'lat' => -7.25579, 'lng' => 109.19961, 'photo' => 'slamet'],
        ['name' => 'Sumbing', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'sumbing', 'kmz' => true, 'pdf' => false, 'lat' => -7.38476, 'lng' => 110.07247, 'photo' => 'sumbing'],
        ['name' => 'Telomoyo', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'telomoyo', 'kmz' => true, 'pdf' => true, 'lat' => -7.36936, 'lng' => 110.40719, 'photo' => null],
        ['name' => 'Ungaran', 'area' => 'Jawa Tengah', 'region' => 'Jawa Tengah', 'file' => 'ungaran', 'kmz' => true, 'pdf' => true, 'lat' => -7.18535, 'lng' => 110.3497, 'photo' => null],
        ['name' => 'Anjasmoro', 'area' => 'Jawa Timur', 'region' => 'Jawa Timur', 'file' => 'anjasmoro', 'kmz' => true, 'pdf' => true, 'lat' => -7.73414, 'lng' => 112.44496, 'photo' => null],
        ['name' => 'Argopuro', 'area' => 'Jawa Timur', 'region' => 'Jawa Timur', 'file' => 'argopuro', 'kmz' => true, 'pdf' => true, 'lat' => -7.93437, 'lng' => 113.62045, 'photo' => 'argopuro'],
        ['name' => 'Arjuno Welirang', 'area' => 'Jawa Timur', 'region' => 'Jawa Timur', 'file' => 'arjuno-welirang', 'kmz' => true, 'pdf' => true, 'lat' => -7.75489, 'lng' => 112.58653, 'photo' => 'arjuno'],
        ['name' => 'Butak', 'area' => 'Jawa Timur', 'region' => 'Jawa Timur', 'file' => 'butak', 'kmz' => true, 'pdf' => false, 'lat' => -7.94438, 'lng' => 112.47113, 'photo' => null],
        ['name' => 'Ijen', 'area' => 'Jawa Timur', 'region' => 'Jawa Timur', 'file' => 'ijen', 'kmz' => true, 'pdf' => false, 'lat' => -8.06914, 'lng' => 114.23854, 'photo' => 'ijen'],
        ['name' => 'Kelud', 'area' => 'Jawa Timur', 'region' => 'Jawa Timur', 'file' => 'kelud', 'kmz' => true, 'pdf' => false, 'lat' => -7.94421, 'lng' => 112.32096, 'photo' => null],
        ['name' => 'Liman', 'area' => 'Jawa Timur', 'region' => 'Jawa Timur', 'file' => 'liman', 'kmz' => true, 'pdf' => false, 'lat' => -7.7787, 'lng' => 111.73855, 'photo' => null],
        ['name' => 'Penanggungan', 'area' => 'Jawa Timur', 'region' => 'Jawa Timur', 'file' => 'penanggungan', 'kmz' => true, 'pdf' => true, 'lat' => -7.61168, 'lng' => 112.61186, 'photo' => null],
        ['name' => 'Raung', 'area' => 'Jawa Timur', 'region' => 'Jawa Timur', 'file' => 'raung', 'kmz' => true, 'pdf' => true, 'lat' => -8.14453, 'lng' => 114.02374, 'photo' => 'raung'],
        ['name' => 'Semeru', 'area' => 'Jawa Timur', 'region' => 'Jawa Timur', 'file' => 'semeru', 'kmz' => true, 'pdf' => true, 'lat' => -8.07098, 'lng' => 112.92364, 'photo' => 'semeru'],
        ['name' => 'Abang', 'area' => 'Bali', 'region' => 'Bali', 'file' => 'abang', 'kmz' => true, 'pdf' => false, 'lat' => -8.27308, 'lng' => 115.42221, 'photo' => null],
        ['name' => 'Adeng', 'area' => 'Bali', 'region' => 'Bali', 'file' => 'adeng', 'kmz' => true, 'pdf' => false, 'lat' => -8.32722, 'lng' => 115.14286, 'photo' => null],
        ['name' => 'Agung', 'area' => 'Bali', 'region' => 'Bali', 'file' => 'agung', 'kmz' => true, 'pdf' => true, 'lat' => -8.35089, 'lng' => 115.49574, 'photo' => null],
        ['name' => 'Batukaru', 'area' => 'Bali', 'region' => 'Bali', 'file' => 'batukaru', 'kmz' => true, 'pdf' => false, 'lat' => -8.34171, 'lng' => 115.09167, 'photo' => null],
        ['name' => 'Batur', 'area' => 'Bali', 'region' => 'Bali', 'file' => 'batur', 'kmz' => true, 'pdf' => false, 'lat' => -8.24519, 'lng' => 115.37214, 'photo' => null],
        ['name' => 'Catur', 'area' => 'Bali', 'region' => 'Bali', 'file' => 'catur', 'kmz' => true, 'pdf' => false, 'lat' => -8.25981, 'lng' => 115.19177, 'photo' => null],
        ['name' => 'Lesung', 'area' => 'Bali', 'region' => 'Bali', 'file' => 'lesung', 'kmz' => true, 'pdf' => false, 'lat' => -8.28164, 'lng' => 115.10351, 'photo' => null],
        ['name' => 'Pohen', 'area' => 'Bali', 'region' => 'Bali', 'file' => 'pohen', 'kmz' => true, 'pdf' => false, 'lat' => -8.29349, 'lng' => 115.13844, 'photo' => null],
        ['name' => 'Sanghyang', 'area' => 'Bali', 'region' => 'Bali', 'file' => 'sanghyang', 'kmz' => true, 'pdf' => false, 'lat' => -8.30072, 'lng' => 115.10129, 'photo' => null],
        ['name' => 'Tapak', 'area' => 'Bali', 'region' => 'Bali', 'file' => 'tapak', 'kmz' => true, 'pdf' => false, 'lat' => -8.27509, 'lng' => 115.14403, 'photo' => null],
        ['name' => 'Rinjani', 'area' => 'Nusa Tenggara Barat', 'region' => 'Nusa Tenggara Barat', 'file' => 'rinjani', 'kmz' => true, 'pdf' => true, 'lat' => -8.38505, 'lng' => 116.43962, 'photo' => 'rinjani'],
        ['name' => 'Tambora', 'area' => 'Nusa Tenggara Barat', 'region' => 'Nusa Tenggara Barat', 'file' => 'tambora', 'kmz' => true, 'pdf' => false, 'lat' => -8.27466, 'lng' => 117.94808, 'photo' => null],
        ['name' => 'Mutis', 'area' => 'Nusa Tenggara Timur', 'region' => 'Nusa Tenggara Timur', 'file' => 'mutis', 'kmz' => true, 'pdf' => true, 'lat' => -9.60475, 'lng' => 124.22638, 'photo' => null],
        ['name' => 'Geureudong', 'area' => 'Aceh', 'region' => 'Sumatera', 'file' => 'aceh-geureudong', 'kmz' => true, 'pdf' => false, 'lat' => 4.82183, 'lng' => 96.78804, 'photo' => null],
        ['name' => 'Kemiri', 'area' => 'Aceh', 'region' => 'Sumatera', 'file' => 'aceh-kemiri', 'kmz' => true, 'pdf' => false, 'lat' => 3.80251, 'lng' => 97.50373, 'photo' => null],
        ['name' => 'Leuser', 'area' => 'Aceh', 'region' => 'Sumatera', 'file' => 'aceh-leuser', 'kmz' => true, 'pdf' => false, 'lat' => 3.85651, 'lng' => 97.19655, 'photo' => null],
        ['name' => 'Telong', 'area' => 'Aceh', 'region' => 'Sumatera', 'file' => 'aceh-telong', 'kmz' => true, 'pdf' => false, 'lat' => 4.76937, 'lng' => 96.81465, 'photo' => null],
        ['name' => 'Danau Gunung Tujuh', 'area' => 'Jambi', 'region' => 'Sumatera', 'file' => 'jambi-danau-gunung-tujuh', 'kmz' => true, 'pdf' => false, 'lat' => -1.71302, 'lng' => 101.36915, 'photo' => null],
        ['name' => 'Kerinci', 'area' => 'Jambi', 'region' => 'Sumatera', 'file' => 'jambi-kerinci', 'kmz' => true, 'pdf' => true, 'lat' => -1.75138, 'lng' => 101.26435, 'photo' => 'kerinci'],
        ['name' => 'Masurai', 'area' => 'Jambi', 'region' => 'Sumatera', 'file' => 'jambi-masurai', 'kmz' => true, 'pdf' => false, 'lat' => -2.48406, 'lng' => 101.84845, 'photo' => null],
        ['name' => 'Marapi', 'area' => 'Sumatera Barat', 'region' => 'Sumatera', 'file' => 'sumatera-barat-marapi', 'kmz' => true, 'pdf' => false, 'lat' => -0.39138, 'lng' => 100.44546, 'photo' => null],
        ['name' => 'Pantai Cermin', 'area' => 'Sumatera Barat', 'region' => 'Sumatera', 'file' => 'sumatera-barat-pantai-cermin', 'kmz' => true, 'pdf' => false, 'lat' => -1.27424, 'lng' => 100.82961, 'photo' => null],
        ['name' => 'Sago', 'area' => 'Sumatera Barat', 'region' => 'Sumatera', 'file' => 'sumatera-barat-sago', 'kmz' => true, 'pdf' => false, 'lat' => -0.31892, 'lng' => 100.65661, 'photo' => null],
        ['name' => 'Singgalang Tandiket', 'area' => 'Sumatera Barat', 'region' => 'Sumatera', 'file' => 'sumatera-barat-singgalang-tandiket', 'kmz' => true, 'pdf' => false, 'lat' => -0.4102, 'lng' => 100.35202, 'photo' => null],
        ['name' => 'Talakmau', 'area' => 'Sumatera Barat', 'region' => 'Sumatera', 'file' => 'sumatera-barat-talakmau', 'kmz' => true, 'pdf' => false, 'lat' => 0.10512, 'lng' => 99.94836, 'photo' => null],
        ['name' => 'Talang', 'area' => 'Sumatera Barat', 'region' => 'Sumatera', 'file' => 'sumatera-barat-talang', 'kmz' => true, 'pdf' => false, 'lat' => -0.97156, 'lng' => 100.69256, 'photo' => null],
        ['name' => 'Dempo', 'area' => 'Sumatera Selatan', 'region' => 'Sumatera', 'file' => 'sumatera-selatan-dempo', 'kmz' => true, 'pdf' => false, 'lat' => -4.02889, 'lng' => 103.14936, 'photo' => null],
        ['name' => 'Sibayak', 'area' => 'Sumatera Utara', 'region' => 'Sumatera', 'file' => 'sumatera-utara-sibayak', 'kmz' => true, 'pdf' => false, 'lat' => 3.23864, 'lng' => 98.50364, 'photo' => null],
        ['name' => 'Sibuatan', 'area' => 'Sumatera Utara', 'region' => 'Sumatera', 'file' => 'sumatera-utara-sibuatan', 'kmz' => true, 'pdf' => false, 'lat' => 2.91945, 'lng' => 98.44271, 'photo' => null],
        ['name' => 'Sinabung', 'area' => 'Sumatera Utara', 'region' => 'Sumatera', 'file' => 'sumatera-utara-sinabung', 'kmz' => true, 'pdf' => false, 'lat' => 3.17919, 'lng' => 98.39403, 'photo' => null],
        ['name' => 'Sorikmarapi', 'area' => 'Sumatera Utara', 'region' => 'Sumatera', 'file' => 'sumatera-utara-sorikmarapi', 'kmz' => true, 'pdf' => false, 'lat' => 0.6924, 'lng' => 99.54787, 'photo' => null],
        ['name' => 'Gandang Dewata', 'area' => 'Sulawesi Barat', 'region' => 'Sulawesi', 'file' => 'sulawesi-barat-gandang-dewata', 'kmz' => true, 'pdf' => true, 'lat' => -2.8119, 'lng' => 119.3563, 'photo' => null],
        ['name' => 'Bawakaraeng-Lompobatang', 'area' => 'Sulawesi Selatan', 'region' => 'Sulawesi', 'file' => 'sulawesi-selatan-bawakaraeng-lompobatang', 'kmz' => true, 'pdf' => false, 'lat' => -5.31057, 'lng' => 119.9227, 'photo' => null],
        ['name' => 'Rantemario-Latimojong', 'area' => 'Sulawesi Selatan', 'region' => 'Sulawesi', 'file' => 'sulawesi-selatan-rantemario-latimojong', 'kmz' => true, 'pdf' => false, 'lat' => -3.4018, 'lng' => 120.01545, 'photo' => null],
        ['name' => 'Bulu Nti', 'area' => 'Sulawesi Tengah', 'region' => 'Sulawesi', 'file' => 'sulawesi-tengah-bulu-nti', 'kmz' => true, 'pdf' => false, 'lat' => -1.06284, 'lng' => 119.83251, 'photo' => null],
        ['name' => 'Gawalise', 'area' => 'Sulawesi Tengah', 'region' => 'Sulawesi', 'file' => 'sulawesi-tengah-gawalise', 'kmz' => true, 'pdf' => false, 'lat' => -0.88936, 'lng' => 119.79553, 'photo' => null],
        ['name' => 'Nokilalaki', 'area' => 'Sulawesi Tengah', 'region' => 'Sulawesi', 'file' => 'sulawesi-tengah-nokilalaki', 'kmz' => true, 'pdf' => false, 'lat' => -1.2349, 'lng' => 120.15757, 'photo' => null],
        ['name' => 'Mekonga', 'area' => 'Sulawesi Tenggara', 'region' => 'Sulawesi', 'file' => 'sulawesi-tenggara-mekonga', 'kmz' => true, 'pdf' => false, 'lat' => -3.64172, 'lng' => 121.15959, 'photo' => null],
        ['name' => 'Klabat', 'area' => 'Sulawesi Utara', 'region' => 'Sulawesi', 'file' => 'sulawesi-utara-klabat', 'kmz' => true, 'pdf' => false, 'lat' => 1.44188, 'lng' => 125.01249, 'photo' => null],
        ['name' => 'Soputan', 'area' => 'Sulawesi Utara', 'region' => 'Sulawesi', 'file' => 'sulawesi-utara-soputan', 'kmz' => true, 'pdf' => false, 'lat' => 1.13052, 'lng' => 124.74968, 'photo' => null],
        ['name' => 'Binaiya', 'area' => 'Maluku', 'region' => 'Maluku', 'file' => 'binaiya', 'kmz' => true, 'pdf' => true, 'lat' => -3.23277, 'lng' => 129.41852, 'photo' => null],
        ['name' => 'Bukit Kelam', 'area' => 'Kalimantan Barat', 'region' => 'Kalimantan', 'file' => 'kalimantan-barat-bukit-kelam', 'kmz' => true, 'pdf' => true, 'lat' => 0.07716, 'lng' => 111.64366, 'photo' => null],
        ['name' => 'Bukit Raya', 'area' => 'Kalimantan Tengah', 'region' => 'Kalimantan', 'file' => 'kalimantan-tengah-bukit-raya', 'kmz' => true, 'pdf' => false, 'lat' => -0.60248, 'lng' => 112.63776, 'photo' => null],
    ],

];
