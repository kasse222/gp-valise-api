<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\KycRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class KycFileController extends Controller
{
    public function show(Request $request, KycRequest $kycRequest, string $field): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        abort_unless(
            in_array($field, ['id_front_path', 'id_back_path'], true),
            404
        );

        $path = $kycRequest->{$field};

        abort_if(empty($path), 404, 'Document non fourni.');

        abort_unless(
            Storage::disk('private')->exists($path),
            404,
            'Fichier introuvable.'
        );

        $contents = Storage::disk('private')->get($path);

        // Détecter le type MIME depuis l'extension
        $ext      = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeMap  = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'pdf'  => 'application/pdf',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';

        return response($contents, 200, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => 'inline; filename="' . basename($path) . '"',
            'Cache-Control'          => 'no-store, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
