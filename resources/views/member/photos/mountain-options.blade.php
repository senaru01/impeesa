{{-- Daftar saran gunung untuk isian lokasi foto. Parameter: $id. --}}
<datalist id="{{ $id }}">
    @foreach (\App\Models\Climb::mountainOptions() as $name => $elevation)
        <option value="{{ $name }}"></option>
    @endforeach
</datalist>
