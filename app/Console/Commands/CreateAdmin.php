<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateAdmin extends Command
{
    protected $signature = 'service-desk:create-admin {email : Correo del administrador} {--owner : Registrar al propietario protegido} {--force : Confirmar ejecución en producción}';
    protected $description = 'Crea un administrador con contraseña solicitada de forma privada en consola';

    public function handle(): int
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('En producción debes utilizar --force.');
            return self::FAILURE;
        }

        $email = trim(strtolower((string) $this->argument('email')));
        $validation = Validator::make(['email' => $email], [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
        ]);
        if ($validation->fails()) {
            $this->error($validation->errors()->first());
            return self::FAILURE;
        }

        $password = $this->secret('Contraseña (mínimo 16 caracteres)');
        $repeat = $this->secret('Repite la contraseña');
        if (!is_string($password) || strlen($password) < 16 || $password !== $repeat) {
            $this->error('La contraseña no cumple la longitud mínima o no coincide.');
            return self::FAILURE;
        }

        if ($this->option('owner') && User::where('is_owner', true)->exists()) {
            $this->error('Ya existe una cuenta propietaria; no se creará otra.');
            return self::FAILURE;
        }

        $user = User::create([
            'name' => (string) $this->ask('Nombre del administrador', 'Administrador'),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
            'active' => true,
        ]);
        if ($this->option('owner')) {
            $user->forceFill(['is_owner' => true])->save();
        }
        $this->info('Administrador creado correctamente.');
        return self::SUCCESS;
    }
}
