<?php

/*
|--------------------------------------------------------------------------
| Mitra Destinasi
|--------------------------------------------------------------------------
|
| Operator lokal yang menjadi rujukan untuk destinasi tertentu, dikunci
| dengan slug gunung di config/mountains.php. Destinasi yang terdaftar di
| sini punya halaman detail sendiri; isinya ringkasan umum dari situs mitra,
| sedangkan informasi lengkap (harga, itinerary, pemesanan) diarahkan ke
| situs mereka.
|
*/

return [

    'rinjani' => [
        'name' => 'Iza Rinjani',
        'url' => 'https://izarinjani.com/',
        'tagline' => 'Petualangan Rinjanimu dimulai di sini.',
        'about' => 'Iza Rinjani adalah operator trekking lokal yang berbasis di Desa Senaru, Lombok Utara. Mereka menyediakan pendakian terorganisir dengan pemandu lokal berpengalaman, porter profesional dan transportasi, serta membantu mulai dari informasi jalur, pemilihan paket hingga pemesanan.',
        'basecamp' => 'Iza Homestay di Senaru menjadi basecamp para pendaki, dengan pemandangan langsung ke arah Rinjani.',

        'stats' => [
            ['label' => 'Puncak', 'value' => '3.726 m'],
            ['label' => 'Jalur', 'value' => '4'],
            ['label' => 'Durasi', 'value' => '1–4 hari'],
        ],

        'routes' => [
            ['name' => 'Sembalun', 'label' => 'Jalur puncak', 'description' => 'Menuju puncak 3.726 m melintasi hamparan sabana yang luas.'],
            ['name' => 'Senaru', 'label' => 'Jalur klasik crater rim', 'description' => 'Dimulai dari hutan hujan lebat hingga Senaru Crater Rim 2.641 m dengan panorama Danau Segara Anak.'],
            ['name' => 'Torean', 'label' => 'Jalur petualangan', 'description' => 'Lembah yang indah, air terjun, tebing curam dan pemandian air panas alami.'],
            ['name' => 'Timbanuh', 'label' => 'Jalur menantang', 'description' => 'Medan yang kasar dan lanskap vulkanik untuk pendaki yang mencari tantangan.'],
        ],

        'packages' => [
            ['name' => 'One Day Trek Senaru Crater Rim', 'duration' => '1 hari', 'route' => 'Senaru'],
            ['name' => 'Summit Trek 2D1N', 'duration' => '2 hari 1 malam', 'route' => 'Sembalun'],
            ['name' => 'Senaru Crater Rim 2D1N', 'duration' => '2 hari 1 malam', 'route' => 'Senaru'],
            ['name' => 'Summit & Danau Segara Anak 3D2N', 'duration' => '3 hari 2 malam', 'route' => 'Sembalun – Senaru'],
            ['name' => 'Rinjani Trekking 4D3N', 'duration' => '4 hari 3 malam', 'route' => 'Lintas jalur'],
        ],

        'includes' => ['Pemandu lokal berpengalaman', 'Porter profesional', 'Transportasi'],

        'contact' => [
            'whatsapp' => '6287732525591',
            'whatsapp_label' => '+62 877-3252-5591',
            'email' => 'diditrekking@gmail.com',
            'instagram' => 'didi_trekking',
            'address' => 'Jl. Pariwisata Senaru, Desa Senaru, Bayan, Lombok Utara',
        ],
    ],

];
