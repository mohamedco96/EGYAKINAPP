<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ManageAppleClientSecret extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apple:manage-secret 
                            {action : Action to perform (generate, check, renew)}
                            {--env= : Environment (dev, staging, prod)}
                            {--auto-renew : Automatically renew if expired}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Apple Client Secret with automatic renewal';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $env = $this->option('env') ?: 'dev';

        switch ($action) {
            case 'check':
                return $this->checkSecretExpiration($env);
            case 'generate':
                return $this->generateNewSecret($env);
            case 'renew':
                return $this->renewSecret($env);
            default:
                $this->error('Invalid action. Use: check, generate, or renew');

                return 1;
        }
    }

    /**
     * Check if current secret is expired or will expire soon
     */
    private function checkSecretExpiration($env)
    {
        $envFile = $this->getEnvFile($env);

        if (! file_exists($envFile)) {
            $this->error("Environment file {$envFile} not found!");

            return 1;
        }

        $envContent = file_get_contents($envFile);

        if (! preg_match('/^APPLE_CLIENT_SECRET=(.+)$/m', $envContent, $matches)) {
            $this->error('APPLE_CLIENT_SECRET not found in environment file');

            return 1;
        }

        $clientSecret = $matches[1];
        $expiration = $this->getSecretExpiration($clientSecret);

        if (! $expiration) {
            $this->warn('Could not determine expiration date from client secret');

            return 1;
        }

        $daysUntilExpiry = (int) (($expiration - time()) / 86400);

        $this->info("Apple Client Secret Status for {$env}");
        $this->info('=====================================');
        $this->line('Current Secret: '.substr($clientSecret, 0, 20).'...');
        $this->line('Expires: '.date('Y-m-d H:i:s', $expiration));
        $this->line('Days until expiry: '.$daysUntilExpiry);

        if ($daysUntilExpiry <= 0) {
            $this->error('❌ Secret is EXPIRED!');
            if ($this->option('auto-renew')) {
                $this->info('Auto-renewing...');

                return $this->renewSecret($env);
            } else {
                $this->warn('Run: php artisan apple:manage-secret renew --env='.$env);
            }
        } elseif ($daysUntilExpiry <= 30) {
            $this->warn('⚠️  Secret expires in '.$daysUntilExpiry.' days');
            if ($this->option('auto-renew')) {
                $this->info('Auto-renewing...');

                return $this->renewSecret($env);
            } else {
                $this->warn('Consider renewing soon: php artisan apple:manage-secret renew --env='.$env);
            }
        } else {
            $this->info('✅ Secret is valid for '.$daysUntilExpiry.' more days');
        }

        return 0;
    }

    /**
     * Generate a new client secret
     */
    private function generateNewSecret($env)
    {
        $this->info("Generating new Apple Client Secret for {$env}...");

        // Get configuration from environment
        $config = $this->getAppleConfig($env);

        if (! $config) {
            $this->error('Apple configuration not found in environment file');

            return 1;
        }

        try {
            $clientSecret = $this->generateAppleClientSecret(
                $config['team_id'],
                $config['client_id'],
                $config['key_id'],
                $config['private_key']
            );

            $this->saveSecretToEnv($clientSecret, $env);

            $this->info('✅ New Apple Client Secret generated and saved!');
            $this->line('Expires: '.date('Y-m-d H:i:s', time() + 86400 * 180));

            return 0;

        } catch (Exception $e) {
            $this->error('Error generating client secret: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Renew expired or soon-to-expire secret
     */
    private function renewSecret($env)
    {
        $this->info("Renewing Apple Client Secret for {$env}...");

        return $this->generateNewSecret($env);
    }

    /**
     * Get Apple configuration from environment file
     */
    private function getAppleConfig($env)
    {
        $envFile = $this->getEnvFile($env);

        if (! file_exists($envFile)) {
            return null;
        }

        $envContent = file_get_contents($envFile);

        $config = [];
        $required = ['APPLE_TEAM_ID', 'APPLE_CLIENT_ID', 'APPLE_KEY_ID', 'APPLE_PRIVATE_KEY'];

        foreach ($required as $key) {
            if (preg_match("/^{$key}=(.+)$/m", $envContent, $matches)) {
                $config[strtolower(str_replace('APPLE_', '', $key))] = $matches[1];
            } else {
                $this->error("Missing {$key} in environment file");

                return null;
            }
        }

        return $config;
    }

    /**
     * Generate Apple Client Secret JWT token
     */
    private function generateAppleClientSecret($teamId, $clientId, $keyId, $privateKey)
    {
        $header = [
            'alg' => 'ES256',
            'kid' => $keyId,
        ];

        $payload = [
            'iss' => $teamId,
            'iat' => time(),
            'exp' => time() + 86400 * 180, // 6 months from now
            'aud' => 'https://appleid.apple.com',
            'sub' => $clientId,
        ];

        $headerEncoded = $this->base64url_encode(json_encode($header));
        $payloadEncoded = $this->base64url_encode(json_encode($payload));

        $signature = '';
        $success = openssl_sign(
            $headerEncoded.'.'.$payloadEncoded,
            $signature,
            $privateKey,
            OPENSSL_ALGO_SHA256
        );

        if (! $success) {
            throw new Exception('Failed to sign JWT token');
        }

        $signatureEncoded = $this->base64url_encode($signature);

        return $headerEncoded.'.'.$payloadEncoded.'.'.$signatureEncoded;
    }

    /**
     * Get expiration date from JWT token
     */
    private function getSecretExpiration($clientSecret)
    {
        try {
            $parts = explode('.', $clientSecret);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);

            return $payload['exp'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Save secret to environment file
     */
    private function saveSecretToEnv($clientSecret, $env)
    {
        $envFile = $this->getEnvFile($env);

        if (! file_exists($envFile)) {
            File::put($envFile, '');
        }

        $envContent = file_get_contents($envFile);

        // Update or add APPLE_CLIENT_SECRET
        if (preg_match('/^APPLE_CLIENT_SECRET=.*$/m', $envContent)) {
            $envContent = preg_replace('/^APPLE_CLIENT_SECRET=.*$/m', "APPLE_CLIENT_SECRET={$clientSecret}", $envContent);
        } else {
            $envContent .= "\nAPPLE_CLIENT_SECRET={$clientSecret}\n";
        }

        File::put($envFile, $envContent);
    }

    /**
     * Get environment file path
     */
    private function getEnvFile($env)
    {
        return match ($env) {
            'dev' => '.env',
            'staging' => '.env.staging',
            'prod' => '.env.production',
            default => '.env'
        };
    }

    /**
     * Base64 URL encode
     */
    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
