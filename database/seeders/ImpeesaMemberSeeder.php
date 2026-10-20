<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Anggota dari artikel "Profil Singkat Anggota" (impeesaxtream.wordpress.com, 12 Maret 2016).
 *
 * Setiap akun memakai email nama.anggota@impeesa-xtream.com dengan password sementara
 * yang wajib diganti setelah login pertama. Foto profil disalin dari folder data/profil-anggota.
 */
class ImpeesaMemberSeeder extends Seeder
{
    use WithoutModelEvents;

    public const TEMPORARY_PASSWORD = 'Sahabat@456';

    public const EMAIL_DOMAIN = 'impeesa-xtream.com';

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (self::members() as $index => $profile) {
            $number = $index + 1;

            $member = User::firstOrCreate(['email' => self::emailFor($profile['name'])], [
                ...$profile,
                'password' => self::TEMPORARY_PASSWORD,
                'role' => Role::Member,
                'is_active' => true,
                'position' => $profile['position'] ?? 'Anggota',
                'member_number' => sprintf('IMP-%04d', $number + 1),
            ]);

            // Detik dibedakan supaya urutan di halaman profil sama dengan artikel aslinya.
            if ($member->wasRecentlyCreated) {
                $member->forceFill(['profile_featured_at' => now()->addSeconds($number)])->save();
            }

            $photo = __DIR__.sprintf('/data/profil-anggota/%02d.jpg', $number);

            if ($member->avatar_path === null && is_file($photo)) {
                $path = "avatars/{$member->id}/profil-singkat.jpg";
                Storage::disk('local')->put($path, file_get_contents($photo));
                $member->update(['avatar_path' => $path]);
            }
        }
    }

    public static function emailFor(string $name): string
    {
        return Str::slug($name, '.').'@'.self::EMAIL_DOMAIN;
    }

    /**
     * Urutan mengikuti artikel aslinya; nomor urut juga dipakai untuk nama file foto.
     *
     * @return list<array{name: string, nickname?: string, gender: string, signature_place?: string, position?: string, birth_place?: string, city?: string, bio: string}>
     */
    public static function members(): array
    {
        return [
            [
                'name' => 'Ahmad Rinjani',
                'gender' => 'L',
                'signature_place' => 'Ranu Kumbolo',
                'bio' => 'Ahmad Rinjani, pria dengan segudang impiannya, memiliki tekad kuat, humoris, dan petualang sejati. Karena sedikit lebih tua dibanding yang lainnya, dia sering dipanggil bapaknya anak-anak.',
            ],
            [
                'name' => 'Achmad Badari',
                'nickname' => 'Masbadz',
                'gender' => 'L',
                'signature_place' => 'Gunung Slamet',
                'city' => 'Kebumen',
                'bio' => 'Achmad Badari, atau biasa disapa masbadz, pria ganteng ini memiliki keahlian seorang planner kegiatan, setiap kegiatan dirinci dengan sangat detail. Pengalaman dalam pendakian gunung sudah tidak diragukan lagi. Pria asal Kebumen ini selain hobi traveling, juga hobi futsal dan main game.',
            ],
            [
                'name' => 'Dimas Aditya',
                'nickname' => 'Dede Dim',
                'gender' => 'L',
                'signature_place' => 'Bangka Belitung',
                'city' => 'Kebumen',
                'bio' => 'Dhimas Aditya, atau sapaan akrabnya dede dim, traveler muda Impeesa dengan pengalaman yang menakjubkan, siap berbagi cerita, tips, dan intrik dalam pendakian. Masalah dapur, dia ahli memasak nasi. Dalam cuaca dingin, menanak nasi menjadi hal yang cukup sulit, namun di tangan dede dim semua jadi mudah. Sama seperti mas badz, dede dim berkebangsaan asli Kebumen.',
            ],
            [
                'name' => 'Bagus Sukma Jati',
                'nickname' => 'Bagus',
                'gender' => 'L',
                'position' => 'Pendiri',
                'birth_place' => 'Purwokerto',
                'bio' => 'Bagus Sukma Jati, salah satu pendiri Impeesa. Dia adalah real leader dalam setiap kegiatan, perencanaannya sangat matang dan penuh perhitungan. Sebagai salah satu pendiri Impeesa, dia tahu betul apa yang menjadi misi Impeesa saat pertama dibentuk hingga bisa sampai sekarang. Lahir di Karang Lewas, Purwokerto, Kabupaten Banyumas, sekarang bekerja di luar Jawa sehingga tidak bisa bergabung dalam kegiatan Impeesa untuk saat ini.',
            ],
            [
                'name' => 'Awal Wahyu Margiono',
                'nickname' => 'Suneo',
                'gender' => 'L',
                'position' => 'Pendiri',
                'city' => 'Jepang',
                'bio' => 'Awal Wahyu Margiono, yang biasa dijuluki suneo, dia juga salah satu pencetus Impeesa. Gayanya yang energik, penuh semangat dan kritis. Suneo sangat teliti dalam hal administrasi, urusan izin dan proposal serahkan pada suneo maka urusan lancar. Sekarang suneo lagi mencari nobita, alias tinggal di negeri sakura, Jepang.',
            ],
            [
                'name' => 'Eka Bangun Purnomo',
                'nickname' => 'Mas Bangun',
                'gender' => 'L',
                'signature_place' => 'Gunung Sumbing',
                'position' => 'Perwira pertama',
                'city' => 'Jepang',
                'bio' => 'Eka Bangun Purnomo, termasuk perwira pertama Impeesa. Kebijaksanaannya sangat dikagumi, seorang yang sangat tenang dalam menghadapi situasi apa pun. Seperti suneo, mas bangun sekarang tinggal di Jepang.',
            ],
            [
                'name' => 'Ari Widodo',
                'nickname' => 'Ardo',
                'gender' => 'L',
                'signature_place' => 'Gunung Sumbing',
                'position' => 'Perwira pertama',
                'birth_place' => 'Banyumas',
                'bio' => 'Ari Widodo, sapaan akrabnya ardo. Seorang yang mempunyai jiwa seni tinggi, sekarang menekuni bisnis di bidang kesenian. Pria kelahiran Banyumas ini juga perwira pertama Impeesa yang ikut dalam pendakian perdana Impeesa ke Gunung Slamet.',
            ],
            [
                'name' => 'Ajun Suhartono',
                'nickname' => 'Mas Ajun',
                'gender' => 'L',
                'position' => 'Perintis',
                'bio' => 'Ajun Suhartono, dari sekian banyak perintis Impeesa, mas ajunlah yang diberi kesempatan mengenyam pendidikan sampai S1 di salah satu perguruan tinggi ternama di Purwokerto, ikut dalam setiap pendakian Impeesa pada awal berdiri. Sampai saat ini masih eksis berkontribusi ke grup Impeesa. Sama seperti ardo dan mas bangun, meski jauh namun tetap peduli dengan Impeesa.',
            ],
            [
                'name' => 'Waluyo',
                'nickname' => 'Pak Wal',
                'gender' => 'L',
                'signature_place' => 'Mahameru',
                'position' => 'Penasehat',
                'bio' => 'Waluyo, inilah penasehat Impeesa. Pria yang biasa disebut pak wal ini mempunyai pengalaman pendakian yang luar biasa, mulai bergabung saat Impeesa kembali hidup di Cikarang. Pak Wal selalu menasehati anggota yang lain, kebijaksanaan dan petuahnya selalu menjadi pertimbangan dalam mengambil keputusan di setiap pendakian.',
            ],
            [
                'name' => 'Kurdianto',
                'gender' => 'L',
                'signature_place' => 'Gunung Merbabu',
                'birth_place' => 'Banyumas',
                'bio' => 'Kurdianto, pria kelahiran Banyumas ini salah satu anggota Unit Bantu Pertolongan Pramuka, divisi khusus dalam bidang SAR (Search and Rescue) Kwarcab Kabupaten Banyumas. Memiliki kemampuan khusus dalam bidang survival, pendaki tangguh yang saat ini masih aktif dalam setiap kegiatan Impeesa.',
            ],
            [
                'name' => 'Wandra Ade Saputra',
                'nickname' => 'Wandra',
                'gender' => 'L',
                'signature_place' => 'Gunung Guntur',
                'bio' => 'Wandra Ade Saputra, biasa dipanggil wandra. Pendaki tangguh yang juga anggota Ubaloka Kwarcab Banyumas, memiliki semangat juang yang tinggi, ambisius dan kritis. Demi mengejar cita-citanya sekarang dia bekerja di bidang pariwisata, bagai Sanji di petualangan Luffy dalam menggapai One Piece.',
            ],
            [
                'name' => 'Mugi Setyo Widodo',
                'nickname' => 'Mugi',
                'gender' => 'L',
                'signature_place' => 'Gunung Batu',
                'birth_place' => 'Banyumas',
                'bio' => 'Mugi Setyo Widodo atau sapaan akrabnya mugi, pria kelahiran Banyumas ini mempunyai keahlian di bidang otomotif, urusan motor kencang serahin ke dia. Urusan badan dia juga yang paling super, namun untuk pendakian jangan diremehkan. Lelaki besar punya cara tersendiri untuk membuktikan dirinya bisa sampai di puncak tertinggi.',
            ],
            [
                'name' => 'Agustino Rekso',
                'nickname' => 'Tino',
                'gender' => 'L',
                'signature_place' => 'Mahameru',
                'bio' => 'Agustino Rekso, sapaan akrabnya tino santoso, dialah fotografer sejati. Pengabadi momen indah setiap perjalanan, karyanya sudah tidak diragukan lagi. Lebih pantas disebut fotografer profesional, namun dia juga sangat suka traveling ke gunung. Momen indah akan semakin indah dalam bingkai jepretannya.',
            ],
            [
                'name' => 'Amin Saefudin',
                'nickname' => 'Mas Amin',
                'gender' => 'L',
                'signature_place' => 'Gunung Ciremai',
                'bio' => 'Amin Saefudin, mas amin adalah seorang pejuang tangguh. Hobinya narsis sehingga senjata utamanya adalah tongsis. Dia mahasiswa di salah satu perguruan tinggi di Jawa Barat, memiliki etos kerja yang tinggi dan dia adalah pendaki handal, biasa diandalkan untuk tim pembuka jalur.',
            ],
            [
                'name' => 'Agus Suprianto',
                'gender' => 'L',
                'signature_place' => 'Gunung Slamet',
                'city' => 'Banyumas',
                'bio' => 'Agus Suprianto, pendaki muda asal Banyumas yang memiliki fisik tangguh, cekatan dan memiliki semangat tinggi. Meski terlihat agak pemalu, namun sebenarnya itulah sikap keramahan dia kepada siapa pun, murah senyum dan rajin menabung. Pendaki muda ini memiliki cukup pengalaman dalam perjalanan, pendakian dan operasi bersih.',
            ],
            [
                'name' => 'Daris Suhendar',
                'nickname' => 'Daris',
                'gender' => 'L',
                'signature_place' => 'Gunung Gede',
                'bio' => 'Daris Suhendar, putra daerah yang memiliki keahlian di bidang musik, sering diundang manggung untuk berbagai macam acara. Di tangannya melodi mengalir indah seperti kabut yang menyelimuti cantigi di ujung puncak, damai dan indah.',
            ],
            [
                'name' => 'Muhammad Dunuroin',
                'gender' => 'L',
                'signature_place' => 'Timika, Papua',
                'city' => 'Timika, Papua',
                'bio' => 'Muhammad Dunuroin, pendaki asal Pemalang yang sekarang bekerja di Freeport Indonesia, khusus mekanik mobil Ford. Sekarang tinggal di Papua, dan jika lancar dialah pendaki pertama dan mungkin satu-satunya pendaki Impeesa yang menapakkan kaki di puncak tertinggi Indonesia, Carstensz Pyramid 4.884 mdpl.',
            ],
            [
                'name' => 'Agus Salim',
                'nickname' => 'Adop',
                'gender' => 'L',
                'signature_place' => 'Gunung Gede',
                'city' => 'Lampung',
                'bio' => 'Agus Salim, atlet futsal yang sangat pandai mengolah si kulit bundar, memiliki fisik prima dan pecinta klub setan merah. Adop, sapaan akrabnya, adalah pria asal Lampung. Setiap pendakian dia selalu berjalan dengan cepat, bagai seorang guide profesional.',
            ],
            [
                'name' => 'Ade Suntoro',
                'nickname' => 'Mas Ade',
                'gender' => 'L',
                'bio' => 'Ade Suntoro, panggilan akrabnya mas ade. Mas ade sudah kaya Jason Statham di film Transporter 3, keahliannya dalam mengemudi sangat luar biasa dan menjadi andalan saat Impeesa mau mengadakan event. Dia juga pendaki yang sangat tangguh, natural born leader mungkin julukan yang pas.',
            ],
            [
                'name' => 'Kodrat Eko Widodo',
                'nickname' => 'Kodrat',
                'gender' => 'L',
                'bio' => 'Kodrat Eko Widodo, anak muda yang satu ini berwajah seperti boy band, namun boy band era reformasi. Ketertarikannya dengan alam membawanya ke pengalaman baru, pengalamannya termasuk luar biasa untuk pemuda seusianya. Inilah generasi mendatang pendaki Indonesia.',
            ],
            [
                'name' => 'Khoirul',
                'nickname' => 'Kidz Zaman Now',
                'gender' => 'L',
                'city' => 'Madiun',
                'bio' => 'Khoirul, pria asli Madiun ini sangat akrab dipanggil kidz zaman now. Di samping kebiasaannya yang sadar kamera, hobinya juga scroll dan like media sosial. Namun di balik semua itu, ia adalah pemuda penuh semangat, memiliki cita-cita dan imajinasi tinggi.',
            ],
            [
                'name' => 'Adi Dwi',
                'nickname' => 'Mas Adi',
                'gender' => 'L',
                'bio' => 'Adi Dwi, mas adi adalah tipe orang melankolis. Dia adalah programmer handal yang cukup terkenal di dunia persilatan, dia selalu mendaki saat butuh refreshing dari deretan coding yang selalu ia geluti setiap hari.',
            ],
            [
                'name' => 'Vera',
                'gender' => 'P',
                'signature_place' => 'Kampung Bambu',
                'birth_place' => 'Banjarnegara',
                'bio' => 'Vera, princess Impeesa yang satu ini adalah cewek yang sangat handal, mendaki layaknya cowok, tak kenal lelah dan tak pernah mau diam. Wanita kelahiran Banjarnegara ini sudah sering melakukan pendakian dan traveling, dan dia adalah rider sejati yang selalu berkelana dengan mas rio (julukan motor merah kesayangannya).',
            ],
            [
                'name' => 'Mutiara Nurul Asri',
                'nickname' => 'Tira',
                'gender' => 'P',
                'signature_place' => 'Mahameru',
                'bio' => 'Mutiara Nurul Asri, biasa dipanggil tira. Kalau dikata motor, inilah si gesit irit. Gerakannya yang gesit namun sangat susah makan kalau di gunung. Pernah menapakkan kaki ke Mahameru, tekadnya sangat tinggi dan tak kenal lelah. Sebagai seorang admin, jari jemarinya sangat lincah di atas keyboard, bahkan lebih lincah dari gerakan tupai di ranting cemara.',
            ],
            [
                'name' => 'Cici Armiarsi',
                'nickname' => 'Cici',
                'gender' => 'P',
                'signature_place' => 'Gunung Slamet',
                'bio' => 'Cici Armiarsi, atau sapaan aslinya cici. Dipanggil cici bukan karena dia keturunan Tionghoa, tapi memang nama dia cici. Dia cewek yang super duper pokoknya, pendaki tangguh dan tak kenal lelah plus paling rame. Asal ada dia dalam pendakian pasti gak bakal sepi, asal doi tidak sedang ngambek yah...',
            ],
            [
                'name' => 'Risdhiyanti Sri Handayani',
                'nickname' => 'Riris',
                'gender' => 'P',
                'signature_place' => 'Gunung Andong',
                'birth_place' => 'Jepara',
                'bio' => 'Risdhiyanti Sri Handayani, panggilan akrabnya riris. Menyebut namanya seperti menyebut ibu tanah kelahirannya, RA Kartini. Benar, dia lahir di Jepara sekian tahun yang lalu. Di balik badannya yang gak begitu akrab dengan suhu dingin, tersimpan tekad yang sangat luar biasa.',
            ],
            [
                'name' => 'Anita Sugandi',
                'nickname' => 'Nita',
                'gender' => 'P',
                'signature_place' => 'Jogja',
                'bio' => 'Anita Sugandi, biasa dipanggil nita. Putri Impeesa yang satu ini orangnya kalem, rajin menabung, suka banget sama es krim, dan sekali main pasti jauh. Sebagai wanita karier sekarang sudah jarang naik gunung, katanya sih menunggu pangeran ber-carrier menculiknya.',
            ],
            [
                'name' => 'Yuniar',
                'nickname' => 'De Niar',
                'gender' => 'P',
                'bio' => 'Yuniar, ini dia putri termuda Impeesa. Karena termuda, dia sering dipanggil de niar. Meski rupawan kaya bidadari yang turun dari mana gak tahu, jangan berani-berani ganggu dia, karena ada kami yang akan selalu menjaganya.',
            ],
            [
                'name' => 'Ayu',
                'nickname' => 'Kak Ayu',
                'gender' => 'P',
                'signature_place' => 'Gunung Slamet',
                'city' => 'Cirebon',
                'bio' => 'Ayu, seperti namanya, kak ayu asli Cirebon. Pertama berjumpa di jalur pendakian Merbabu, bagai seorang saudara dia langsung akrab dengan kami, dan dengan bantuan dialah tim ekspedisi Impeesa ke Ciremai berjalan lancar. Benar, dialah orang yang paling tepat untuk sumber informasi pendakian gunung tertinggi di Jawa Barat.',
            ],
            [
                'name' => 'Dwi Setyo Ningsih',
                'nickname' => 'Teh Wi',
                'gender' => 'P',
                'signature_place' => '3.676 mdpl',
                'city' => 'Cianjur',
                'bio' => 'Dwi Setyo Ningsih, biasa kami memanggilnya teh wi. Dari fotonya sudah tergambar betapa kuat tekadnya, dari beberapa pendakian dia selalu berhasil melewatinya dengan mudah. Kalau berbicara Gede Pangrango, Taman Bunga atau Istana Cipanas, dia adalah orang yang paling tepat untuk dijadikan sumber info, karena memang Cianjur adalah kampung halamannya.',
            ],
        ];
    }
}
