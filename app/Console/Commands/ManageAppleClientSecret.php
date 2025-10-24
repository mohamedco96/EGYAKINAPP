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
                            {action : Action to perform (generate, check, renew, debug)}
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
            case 'debug':
                return $this->debugConfiguration($env);
            default:
                $this->error('Invalid action. Use: check, generate, renew, or debug');

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
     * Debug Apple configuration
     */
    private function debugConfiguration($env)
    {
        $this->info("Debugging Apple Configuration for {$env}...");
        $this->line('=====================================');

        $envFile = $this->getEnvFile($env);
        $this->line("Environment file: {$envFile}");
        $this->line('File exists: '.(file_exists($envFile) ? 'Yes' : 'No'));

        if (! file_exists($envFile)) {
            $this->error('Environment file not found!');

            return 1;
        }

        $config = $this->getAppleConfig($env);

        if (! $config) {
            $this->error('Failed to load Apple configuration!');

            return 1;
        }

        $this->line("\nConfiguration loaded:");
        $this->line('Team ID: '.($config['team_id'] ?? 'NOT SET'));
        $this->line('Client ID: '.($config['client_id'] ?? 'NOT SET'));
        $this->line('Key ID: '.($config['key_id'] ?? 'NOT SET'));

        $privateKey = $config['private_key'] ?? null;
        if ($privateKey) {
            $this->line('Private Key Length: '.strlen($privateKey).' characters');
            $this->line('Private Key Valid: '.($this->isValidPrivateKey($privateKey) ? 'Yes' : 'No'));
            $this->line('Private Key Preview: '.substr($privateKey, 0, 50).'...');

            // Test OpenSSL
            $this->line("\nOpenSSL Test:");
            $testData = 'test';
            $signature = '';
            $success = openssl_sign($testData, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $this->line('OpenSSL Sign Test: '.($success ? 'SUCCESS' : 'FAILED'));

            if (! $success) {
                $error = openssl_error_string();
                $this->error('OpenSSL Error: '.($error ?: 'Unknown error'));
            }
        } else {
            $this->error('Private Key: NOT SET');
        }

        return 0;
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
            if ($key === 'APPLE_PRIVATE_KEY') {
                // Special handling for multi-line private key
                // Match quoted multi-line values (including newlines) or unquoted values
                if (preg_match("/^{$key}=\"(.+?)\"/ms", $envContent, $matches) ||
                    preg_match("/^{$key}='(.+?)'/ms", $envContent, $matches) ||
                    preg_match("/^{$key}=(.+)(?=^[A-Z_]+=|$)/ms", $envContent, $matches)) {

                    $privateKey = trim($matches[1]);
                    // Convert \n to actual newlines if present
                    $privateKey = str_replace('\\n', "\n", $privateKey);

                    // Ensure the private key has proper markers and formatting
                    if (! str_contains($privateKey, '-----BEGIN PRIVATE KEY-----')) {
                        $privateKey = "-----BEGIN PRIVATE KEY-----\n".$privateKey;
                    }
                    if (! str_contains($privateKey, '-----END PRIVATE KEY-----')) {
                        $privateKey = $privateKey."\n-----END PRIVATE KEY-----";
                    }

                    // Ensure proper newline formatting
                    $privateKey = str_replace('-----BEGIN PRIVATE KEY-----', "-----BEGIN PRIVATE KEY-----\n", $privateKey);
                    $privateKey = str_replace('-----END PRIVATE KEY-----', "\n-----END PRIVATE KEY-----", $privateKey);

                    // Clean up any double newlines
                    $privateKey = preg_replace('/\n\n+/', "\n", $privateKey);

                    $config[strtolower(str_replace('APPLE_', '', $key))] = $privateKey;
                } else {
                    $this->error("Missing {$key} in environment file");

                    return null;
                }
            } else {
                if (preg_match("/^{$key}=(.+)$/m", $envContent, $matches)) {
                    $value = trim($matches[1]);
                    // Remove surrounding quotes if present
                    $value = trim($value, '"\'');
                    $config[strtolower(str_replace('APPLE_', '', $key))] = $value;
                } else {
                    $this->error("Missing {$key} in environment file");

                    return null;
                }
            }
        }

        return $config;
    }

    /**
     * Generate Apple Client Secret JWT token
     */
    private function generateAppleClientSecret($teamId, $clientId, $keyId, $privateKey)
    {
        // Validate private key format
        if (! $this->isValidPrivateKey($privateKey)) {
            throw new Exception('Invalid private key format. Ensure it starts with "-----BEGIN PRIVATE KEY-----" and ends with "-----END PRIVATE KEY-----"');
        }

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
            $error = openssl_error_string();
            throw new Exception('Failed to sign JWT token. OpenSSL error: '.($error ?: 'Unknown error'));
        }

        $signatureEncoded = $this->base64url_encode($signature);

        return $headerEncoded.'.'.$payloadEncoded.'.'.$signatureEncoded;
    }

    /**
     * Validate private key format
     */
    private function isValidPrivateKey($privateKey)
    {
        if (empty($privateKey)) {
            return false;
        }

        // Check if it starts and ends with the correct markers
        $startsWithBegin = strpos($privateKey, '-----BEGIN PRIVATE KEY-----') === 0;
        $endsWithEnd = strpos($privateKey, '-----END PRIVATE KEY-----') !== false;

        return $startsWithBegin && $endsWithEnd;
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
