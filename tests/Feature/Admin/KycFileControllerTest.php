<?php

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

use App\Enums\UserRoleEnum;
use App\Models\KycRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
});

// Crée un faux JPEG minimal sans GD
function fakeJpeg(string $disk, string $path): void
{
    // JPEG header minimal valide (SOI + EOI)
    $jpeg = "\xFF\xD8\xFF\xD9";
    Storage::disk($disk)->put($path, $jpeg);
}

it('streame le fichier recto pour un admin authentifié', function () {
    $path = 'kyc/1/id_front.jpg';
    fakeJpeg('private', $path);

    $admin = User::factory()->create(['role' => UserRoleEnum::ADMIN]);
    $kyc   = KycRequest::factory()->create([
        'user_id'       => User::factory()->create(['role' => UserRoleEnum::SENDER])->id,
        'id_front_path' => $path,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.kyc-files.show', [$kyc->id, 'id_front_path']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');
});

it('retourne 403 pour un non-admin', function () {
    $sender = User::factory()->create(['role' => UserRoleEnum::SENDER]);
    $kyc    = KycRequest::factory()->create(['user_id' => $sender->id]);

    $this->actingAs($sender)
        ->get(route('admin.kyc-files.show', [$kyc->id, 'id_front_path']))
        ->assertForbidden();
});

it('retourne 403 pour un utilisateur non authentifié', function () {
    $kyc = KycRequest::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $this->get(route('admin.kyc-files.show', [$kyc->id, 'id_front_path']))
        ->assertForbidden();
});

it('retourne 404 pour un champ non autorisé', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::ADMIN]);
    $kyc   = KycRequest::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.kyc-files.show', [$kyc->id, 'photo_path']))
        ->assertNotFound();
});

it('retourne 404 si le fichier n\'existe pas sur le disque', function () {
    $admin = User::factory()->create(['role' => UserRoleEnum::ADMIN]);
    $kyc   = KycRequest::factory()->create([
        'user_id'       => User::factory()->create()->id,
        'id_front_path' => 'kyc/1/inexistant.jpg',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.kyc-files.show', [$kyc->id, 'id_front_path']))
        ->assertNotFound();
});

it('retourne 404 si id_back_path est null', function () {
    $path = 'kyc/1/id_front.jpg';
    fakeJpeg('private', $path);

    $admin = User::factory()->create(['role' => UserRoleEnum::ADMIN]);
    $kyc   = KycRequest::factory()->create([
        'user_id'       => User::factory()->create()->id,
        'id_front_path' => $path,
        'id_back_path'  => null,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.kyc-files.show', [$kyc->id, 'id_back_path']))
        ->assertNotFound();
});
