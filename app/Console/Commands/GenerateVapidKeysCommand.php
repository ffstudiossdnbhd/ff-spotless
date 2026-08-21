<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeysCommand extends Command
{
    protected $signature = 'webpush:vapid {--show : Display the keys without modifying .env}';
    protected $description = 'Generate new VAPID public and private keys for Web Push';

    public function handle(): int
    {
        if (class_exists(VAPID::class)) {
            $keys = VAPID::createVapidKeys();
        } else {
            $this->error('Minishlink\WebPush\VAPID is not available. Please run composer install.');

            return self::FAILURE;
        }

        $publicKey = $keys['publicKey'];
        $privateKey = $keys['privateKey'];

        if ($this->option('show')) {
            $this->line('<comment>VAPID_PUBLIC_KEY=</comment>'.$publicKey);
            $this->line('<comment>VAPID_PRIVATE_KEY=</comment>'.$privateKey);

            return self::SUCCESS;
        }

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $content = file_get_contents($envPath);

            if (str_contains($content, 'VAPID_PUBLIC_KEY=')) {
                $content = preg_replace('/^VAPID_PUBLIC_KEY=.*$/m', 'VAPID_PUBLIC_KEY='.$publicKey, $content);
            } else {
                $content .= "\nVAPID_PUBLIC_KEY=".$publicKey;
            }

            if (str_contains($content, 'VAPID_PRIVATE_KEY=')) {
                $content = preg_replace('/^VAPID_PRIVATE_KEY=.*$/m', 'VAPID_PRIVATE_KEY='.$privateKey, $content);
            } else {
                $content .= "\nVAPID_PRIVATE_KEY=".$privateKey;
            }

            file_put_contents($envPath, $content);
            $this->info('VAPID keys generated and updated in .env successfully.');
        } else {
            $this->warn('.env file not found. Here are your generated keys:');
            $this->line('VAPID_PUBLIC_KEY='.$publicKey);
            $this->line('VAPID_PRIVATE_KEY='.$privateKey);
        }

        return self::SUCCESS;
    }
}
