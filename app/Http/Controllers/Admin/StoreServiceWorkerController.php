<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StoreServiceWorkerController extends Controller
{
    /**
     * Service worker aplikasi kasir. Disajikan dari /admin/toko supaya PWA hanya mencakup
     * menu Toko; header Service-Worker-Allowed membolehkan scope "/admin/toko" (tanpa garis miring).
     * Tidak butuh login karena isinya hanya kode statis.
     */
    public function __invoke(): BinaryFileResponse
    {
        return response()->file(public_path('js/toko-sw.js'), [
            'Content-Type' => 'application/javascript; charset=utf-8',
            'Service-Worker-Allowed' => '/admin/toko',
            'Cache-Control' => 'no-cache',
        ]);
    }
}
