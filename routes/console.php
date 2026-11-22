<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('service-desk:stats', function () {
    $this->info('Tickets: '.\App\Models\Ticket::count());
});

Artisan::command('service-desk:create-admin {email}', function (string $email) {
    $email = strtolower(trim($email));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Correo electrónico inválido.');
        return 1;
    }
    if (User::query()->where('email', $email)->exists()) {
        $this->error('Ya existe una cuenta con este correo. No se modifica.');
        return 1;
    }
    $name = trim((string) $this->ask('Nombre del administrador'));
    $password = (string) $this->secret('Contraseña segura (mínimo 16 caracteres)');
    $confirm = (string) $this->secret('Confirmar contraseña');
    if ($name === '' || mb_strlen($name) > 120 || strlen($password) < 16 || $password !== $confirm) {
        $this->error('Datos incompletos, contraseña muy corta o confirmación diferente.');
        return 1;
    }
    User::query()->create([
        'name' => $name,
        'email' => $email,
        'password' => $password,
        'role' => 'admin',
        'active' => true,
    ]);
    $this->info('Administrador creado. La contraseña no se mostró ni se guardó en texto plano.');
    return 0;
})->purpose('Crear de forma interactiva el primer administrador de una instalación nueva.');
