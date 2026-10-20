{{-- Field data diri yang dipakai di halaman profil member dan form anggota super admin. --}}
<p class="m-form__section">Identitas</p>
<div class="m-form__grid">
    <div class="m-field">
        <label for="name">Nama lengkap</label>
        <input id="name" name="name" class="m-input" value="{{ old('name', $person->name) }}" required>
    </div>
    <div class="m-field">
        <label for="nickname">Nama panggilan</label>
        <input id="nickname" name="nickname" class="m-input" value="{{ old('nickname', $person->nickname) }}">
    </div>
    <div class="m-field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" class="m-input" value="{{ old('email', $person->email) }}" required>
    </div>
    <div class="m-field">
        <label for="phone">Telepon / WhatsApp</label>
        <input id="phone" name="phone" class="m-input" value="{{ old('phone', $person->phone) }}">
    </div>
    <div class="m-field">
        <label for="birth_place">Tempat lahir</label>
        <input id="birth_place" name="birth_place" class="m-input" value="{{ old('birth_place', $person->birth_place) }}">
    </div>
    <div class="m-field">
        <label for="birth_date">Tanggal lahir</label>
        <input id="birth_date" name="birth_date" type="date" class="m-input" value="{{ old('birth_date', $person->birth_date?->format('Y-m-d')) }}">
    </div>
    <div class="m-field">
        <label for="gender">Jenis kelamin</label>
        <select id="gender" name="gender" class="m-input">
            <option value="">–</option>
            <option value="L" @selected(old('gender', $person->gender) === 'L')>Laki-laki</option>
            <option value="P" @selected(old('gender', $person->gender) === 'P')>Perempuan</option>
        </select>
    </div>
    <div class="m-field">
        <label for="blood_type">Golongan darah</label>
        <select id="blood_type" name="blood_type" class="m-input">
            <option value="">–</option>
            @foreach (['A', 'B', 'AB', 'O'] as $bloodType)
                <option value="{{ $bloodType }}" @selected(old('blood_type', $person->blood_type) === $bloodType)>{{ $bloodType }}</option>
            @endforeach
        </select>
    </div>
    <div class="m-field">
        <label for="city">Kota domisili</label>
        <input id="city" name="city" class="m-input" value="{{ old('city', $person->city) }}">
    </div>
    <div class="m-field">
        <label for="avatar">Foto profil</label>
        <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="m-input">
        <span class="m-field__hint">JPG, PNG atau WEBP, maks. 4 MB.</span>
    </div>
    <div class="m-field m-form__full">
        <label for="address">Alamat</label>
        <textarea id="address" name="address" class="m-input" rows="2" style="min-height: 70px">{{ old('address', $person->address) }}</textarea>
    </div>
    <div class="m-field m-form__full">
        <label for="bio">Bio singkat</label>
        <textarea id="bio" name="bio" class="m-input" rows="3" placeholder="Gunung favorit, gaya mendaki, target berikutnya...">{{ old('bio', $person->bio) }}</textarea>
        <span class="m-field__hint">Dipakai sebagai cerita di halaman Profil Singkat Anggota jika super admin menampilkan profilmu.</span>
    </div>
    <div class="m-field">
        <label for="signature_place">Tempat kenangan</label>
        <input id="signature_place" name="signature_place" class="m-input" value="{{ old('signature_place', $person->signature_place) }}" placeholder="mis. Ranu Kumbolo, Gunung Slamet">
        <span class="m-field__hint">Lokasi foto profil, tampil di bawah namamu.</span>
    </div>
</div>

<p class="m-form__section">Data KTP</p>
<p class="m-field__hint" style="margin-top: -10px">Hanya tampil di halaman detail darurat untuk membantu identifikasi oleh tim SAR. NIK disimpan terenkripsi.</p>
<div class="m-form__grid">
    <div class="m-field">
        <label for="nik">NIK</label>
        <input id="nik" name="nik" class="m-input" inputmode="numeric" maxlength="16" pattern="\d{16}" value="{{ old('nik', $person->nik) }}" autocomplete="off">
    </div>
    <div class="m-field">
        <label for="nationality">Kewarganegaraan</label>
        <input id="nationality" name="nationality" class="m-input" value="{{ old('nationality', $person->nationality ?? 'WNI') }}">
    </div>
    <div class="m-field">
        <label for="religion">Agama</label>
        <select id="religion" name="religion" class="m-input">
            <option value="">–</option>
            @foreach (\App\Http\Requests\ProfileRequest::RELIGIONS as $religion)
                <option value="{{ $religion }}" @selected(old('religion', $person->religion) === $religion)>{{ $religion }}</option>
            @endforeach
        </select>
    </div>
    <div class="m-field">
        <label for="marital_status">Status perkawinan</label>
        <select id="marital_status" name="marital_status" class="m-input">
            <option value="">–</option>
            @foreach (\App\Http\Requests\ProfileRequest::MARITAL_STATUSES as $status)
                <option value="{{ $status }}" @selected(old('marital_status', $person->marital_status) === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div class="m-field">
        <label for="occupation">Pekerjaan</label>
        <input id="occupation" name="occupation" class="m-input" value="{{ old('occupation', $person->occupation) }}">
    </div>
    <div class="m-field m-form__full">
        <label for="id_card_address">Alamat sesuai KTP</label>
        <textarea id="id_card_address" name="id_card_address" class="m-input" rows="2" style="min-height: 70px" placeholder="Jalan, RT/RW, kelurahan/desa, kecamatan, kabupaten/kota, provinsi">{{ old('id_card_address', $person->id_card_address) }}</textarea>
    </div>
</div>

<p class="m-form__section">Keselamatan & medis</p>
<p class="m-field__hint" style="margin-top: -10px">Data ini ikut tercetak di QR kartu darurat dan tetap terbaca tanpa sinyal.</p>
<div class="m-form__grid">
    <div class="m-field">
        <label for="emergency_contact_name">Nama kontak darurat</label>
        <input id="emergency_contact_name" name="emergency_contact_name" class="m-input" value="{{ old('emergency_contact_name', $person->emergency_contact_name) }}">
    </div>
    <div class="m-field">
        <label for="emergency_contact_relation">Hubungan</label>
        <input id="emergency_contact_relation" name="emergency_contact_relation" class="m-input" value="{{ old('emergency_contact_relation', $person->emergency_contact_relation) }}" placeholder="mis. Istri, Ayah, Kakak">
    </div>
    <div class="m-field">
        <label for="emergency_contact_phone">Telepon kontak darurat</label>
        <input id="emergency_contact_phone" name="emergency_contact_phone" class="m-input" value="{{ old('emergency_contact_phone', $person->emergency_contact_phone) }}">
    </div>
    <div class="m-field">
        <label for="allergies">Alergi</label>
        <input id="allergies" name="allergies" class="m-input" value="{{ old('allergies', $person->allergies) }}" placeholder="Obat, makanan, sengatan serangga...">
    </div>
    <div class="m-field">
        <label for="medications">Obat rutin</label>
        <input id="medications" name="medications" class="m-input" value="{{ old('medications', $person->medications) }}" placeholder="mis. Inhaler salbutamol">
    </div>
    <div class="m-field">
        <label for="medical_notes">Kondisi medis</label>
        <input id="medical_notes" name="medical_notes" class="m-input" value="{{ old('medical_notes', $person->medical_notes) }}" placeholder="Asma, hipertensi, riwayat cedera...">
    </div>
</div>
