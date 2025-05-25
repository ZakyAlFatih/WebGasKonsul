<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
       $projectId = config('firebase.project_id');
    $credentialsFile = config('firebase.credentials');
    $serviceAccountPath = storage_path('app/firebase/' . $credentialsFile);

    if (!file_exists($serviceAccountPath)) {
        throw new \Exception("File service account tidak ditemukan di: $serviceAccountPath");
    }

    if (!is_readable($serviceAccountPath)) {
        throw new \Exception("File service account tidak dapat dibaca di: $serviceAccountPath");
    }


        // Buat satu instance Factory sekali saja
        $factory = (new Factory())
            ->withServiceAccount($serviceAccountPath)
            ->withProjectId($projectId);

        // Daftarkan Auth menggunakan instance factory yang sama
        $this->app->singleton(Auth::class, function ($app) use ($factory) {
            return $factory->createAuth();
        });

        // Daftarkan Firestore menggunakan instance factory yang sama
        $this->app->singleton('firebase.firestore', function ($app) use ($factory) {
            return $factory->createFirestore();
        });
    }
}
