<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'push:vapid-generate';
    protected $description = 'Genera claves VAPID para Web Push';

    public function handle(): int
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            $this->error('No fue posible generar claves VAPID con OpenSSL en este entorno.');
            $this->line('Alternativa: generarlas en otro equipo/servidor y pegarlas en .env');
            $this->line('VAPID_PUBLIC_KEY=...');
            $this->line('VAPID_PRIVATE_KEY=...');
            $this->line('VAPID_SUBJECT=mailto:tu-correo@dominio.com');
            $this->line('Detalle técnico: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Claves VAPID generadas:');
        $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);
        $this->line('VAPID_SUBJECT=mailto:tu-correo@dominio.com');

        return self::SUCCESS;
    }
}
