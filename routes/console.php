<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:user', function () {
    $name = text('Nombre', required: true);
    $username = strtolower(text('Usuario (letras, números, punto, guion o guion bajo)', required: true, validate: fn (string $value) => preg_match('/^[A-Za-z0-9._-]{3,40}$/', $value)
        ? null
        : 'Usa de 3 a 40 caracteres: letras, números, punto, guion o guion bajo.'));
    $secret = password('Contraseña (mínimo 8 caracteres)', required: true, validate: fn (string $value) => strlen($value) < 8
        ? 'Debe tener al menos 8 caracteres.'
        : null);

    $user = User::updateOrCreate(['username' => $username], ['name' => $name, 'password' => $secret]);

    $this->info($user->wasRecentlyCreated
        ? "Usuario {$username} creado. Ya puede entrar en /admin."
        : "Usuario {$username} actualizado con la nueva contraseña.");
})->purpose('Crear o actualizar una cuenta del personal administrativo');
