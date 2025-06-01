<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    // ... (pesan validasi standar lainnya seperti required, email, dll. bisa Anda biarkan atau kustomisasi juga)

    'regex' => ':attribute tidak sesuai format yang benar.', // Pesan regex default jika Anda mau

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you can specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'availability_time1' => [ // Nama field dari request
            'regex' => 'Format Waktu Ketersediaan 1 harus HH:MM - HH:MM (contoh: 09:00 - 12:00).',
        ],
        'availability_time2' => [
            'regex' => 'Format Waktu Ketersediaan 2 harus HH:MM - HH:MM (contoh: 07:30 - 11:30).',
        ],
        'availability_time3' => [
            'regex' => 'Format Waktu Ketersediaan 3 harus HH:MM - HH:MM (contoh: 06:00 - 09:30).',
        ],
        // Anda bisa menambahkan pesan kustom untuk field lain atau aturan lain di sini
        // 'nama_field' => [
        //     'nama_aturan' => 'Pesan kustom Anda.',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'Nama Lengkap',
        'avatar' => 'URL Foto Profil',
        'about' => 'Tentang Saya',
        'phone' => 'Nomor Telepon',
        'availability_day1' => 'Pilihan Hari ke-1',
        'availability_time1' => 'Pilihan Waktu ke-1',
        'availability_day2' => 'Pilihan Hari ke-2',
        'availability_time2' => 'Pilihan Waktu ke-2',
        'availability_day3' => 'Pilihan Hari ke-3',
        'availability_time3' => 'Pilihan Waktu ke-3',
        'password' => 'Password Baru',
        // Tambahkan atribut lain jika perlu
    ],

];