<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;

class GenerateAppleClientSecret extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apple:generate-secret 
                            {--team-id= : Apple Team ID}
                            {--client-id= : Apple Client ID}
                            {--key-id= : Apple Key ID}
                            {--private-key= : Apple Private Key}
                            {--env= : Environment (dev, staging, prod)}
                            {--save-to-env : Save the generated secret to .env file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Apple Sign-In Client Secret JWT token';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Apple Sign-In Client Secret Generator');
        $this->info('=====================================');

        // Get environment
        $env = $this->option('env') ?: $this->choice('Select environment', ['dev', 'staging', 'prod'], 'dev');

        // Get values from options or prompt for them
        $teamId = $this->option('team-id') ?: $this->ask('Enter your Apple Team ID');

        // Suggest client ID based on environment
        $defaultClientId = match ($env) {
            'dev' => 'com.yourcompany.yourapp.dev',
            'staging' => 'com.yourcompany.yourapp.staging',
            'prod' => 'com.yourcompany.yourapp',
            default => 'com.yourcompany.yourapp'
        };

        $clientId = $this->option('client-id') ?: $this->ask('Enter your Apple Client ID', $defaultClientId);
        $keyId = $this->option('key-id') ?: $this->ask('Enter your Apple Key ID');
        $privateKey = $this->option('private-key') ?: $this->ask('Enter your Apple Private Key (full content with headers)');

        if (! $teamId || ! $clientId || ! $keyId || ! $privateKey) {
            $this->error('All parameters are required!');

            return 1;
        }

        try {
            $clientSecret = $this->generateAppleClientSecret($teamId, $clientId, $keyId, $privateKey);

            $this->info('Apple Client Secret Generated Successfully!');
            $this->info('==========================================');
            $this->line('Environment: '.strtoupper($env));
            $this->line('Client ID: '.$clientId);
            $this->line('Client Secret: '.$clientSecret);
            $this->line('Expires: '.date('Y-m-d H:i:s', time() + 86400 * 180));
            $this->newLine();
            $this->info('Add this to your .env file:');
            $this->line('APPLE_CLIENT_SECRET='.$clientSecret);

            // Option to save to .env file
            if ($this->option('save-to-env')) {
                $this->saveToEnvFile($clientSecret, $env);
            }

            return 0;

        } catch (Exception $e) {
            $this->error('Error generating Apple Client Secret: '.$e->getMessage());
            $this->newLine();
            $this->warn('Please check:');
            $this->line('1. Your Team ID is correct');
            $this->line('2. Your Client ID is correct');
            $this->line('3. Your Key ID is correct');
            $this->line('4. Your Private Key is properly formatted');
            $this->line('5. OpenSSL extension is enabled in PHP');

            return 1;
        }
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
     * Base64 URL encode
     */
    private function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Save client secret to environment file
     */
    private function saveToEnvFile($clientSecret, $env)
    {
        $envFile = match ($env) {
            'dev' => '.env',
            'staging' => '.env.staging',
            'prod' => '.env.production',
            default => '.env'
        };

        if (! file_exists($envFile)) {
            $this->warn("Environment file {$envFile} does not exist. Creating it...");
            file_put_contents($envFile, '');
        }

        $envContent = file_get_contents($envFile);

        // Update or add APPLE_CLIENT_SECRET
        if (preg_match('/^APPLE_CLIENT_SECRET=.*$/m', $envContent)) {
            $envContent = preg_replace('/^APPLE_CLIENT_SECRET=.*$/m', "APPLE_CLIENT_SECRET={$clientSecret}", $envContent);
        } else {
            $envContent .= "\nAPPLE_CLIENT_SECRET={$clientSecret}\n";
        }

        file_put_contents($envFile, $envContent);
        $this->info("Client secret saved to {$envFile}");
    }
}
