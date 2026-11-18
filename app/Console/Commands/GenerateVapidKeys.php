<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;
use Throwable;

class GenerateVapidKeys extends Command
{
    protected $signature = 'webpush:vapid {--show : Print the keys instead of writing them to .env} {--force : Replace keys already in .env}';

    protected $description = 'Generate the VAPID key pair used to send web push notifications';

    public function handle(): int
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (Throwable $e) {
            $this->error('Could not create the keys: ' . $e->getMessage());
            if (PHP_OS_FAMILY === 'Windows') {
                $this->line('On Windows/XAMPP, point OPENSSL_CONF at a real openssl.cnf first, e.g.');
                $this->line('  set OPENSSL_CONF=D:\\xampp\\php\\extras\\ssl\\openssl.cnf');
            }

            return self::FAILURE;
        }

        if ($this->option('show')) {
            $this->line('VAPID_PUBLIC_KEY=' . $keys['publicKey']);
            $this->line('VAPID_PRIVATE_KEY=' . $keys['privateKey']);

            return self::SUCCESS;
        }

        $path = base_path('.env');
        $env = file_exists($path) ? file_get_contents($path) : '';

        if (preg_match('/^VAPID_PRIVATE_KEY=.+$/m', $env) && !$this->option('force')) {
            $this->warn('VAPID keys already exist in .env. Changing them drops every existing subscription; use --force to replace them.');

            return self::FAILURE;
        }

        foreach (['VAPID_PUBLIC_KEY' => $keys['publicKey'], 'VAPID_PRIVATE_KEY' => $keys['privateKey']] as $name => $value) {
            $env = preg_match("/^{$name}=.*$/m", $env)
                ? preg_replace("/^{$name}=.*$/m", "{$name}={$value}", $env)
                : rtrim($env) . PHP_EOL . "{$name}={$value}" . PHP_EOL;
        }

        file_put_contents($path, $env);
        $this->info('VAPID keys written to .env. Run `php artisan config:clear` if config is cached.');

        return self::SUCCESS;
    }
}
