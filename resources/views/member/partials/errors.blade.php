@if ($errors->{$bag ?? 'default'}->any())
    <div class="m-errors" role="alert">
        <strong>Periksa kembali isian berikut:</strong>
        <ul>
            @foreach ($errors->{$bag ?? 'default'}->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
