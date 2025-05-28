<?php

return [
    'projects' => [
        'app' => [ // Nama "app" ini bisa Anda sesuaikan jika ingin multiple Firebase projects
            'credentials' => [
                'file' => env('FIREBASE_CREDENTIALS', storage_path('serviceAccountKey.json')),
                // Ganti 'nama-file-kredensial-anda.json' dengan nama file JSON yang sebenarnya
                // Misalnya: 'your-project-id-firebase-adminsdk-xxxxx.json'
            ],
            'project_id' => env('FIREBASE_PROJECT_ID', 'abpx-672e5'),
            // Nilai default di sini akan dipakai jika FIREBASE_PROJECT_ID tidak ada di .env
        ],
    ],
    // Anda juga bisa menambahkan API Key di sini jika Firebase package mendukungnya untuk web client side,
    // tapi untuk Admin SDK biasanya fokus pada kredensial service account.
    // Untuk API Key frontend (client side), biasanya ditaruh di .env langsung dan diakses JS.
    // 'api_key' => env('FIREBASE_API_KEY'), // Ini lebih untuk frontend (JavaScript)
];