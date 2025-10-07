<?php

/**
 * Apple Sign-In Client Secret Generator
 *
 * This script generates the Apple Client Secret JWT token required for Apple Sign-In.
 * Run this script to generate a new client secret when needed.
 */
function generateAppleClientSecret($teamId, $clientId, $keyId, $privateKey)
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

    $headerEncoded = base64url_encode(json_encode($header));
    $payloadEncoded = base64url_encode(json_encode($payload));

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

    $signatureEncoded = base64url_encode($signature);

    return $headerEncoded.'.'.$payloadEncoded.'.'.$signatureEncoded;
}

function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Configuration - Replace these with your actual values
$teamId = 'YOUR_TEAM_ID_HERE';
$clientId = 'com.yourcompany.yourapp';
$keyId = 'YOUR_KEY_ID_HERE';
$privateKey = '-----BEGIN PRIVATE KEY-----
MIGTAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBHkwdwIBAQQg+QS+WUK7/Li2ydbF
YOUR_ACTUAL_PRIVATE_KEY_CONTENT_HERE
-----END PRIVATE KEY-----';

try {
    $clientSecret = generateAppleClientSecret($teamId, $clientId, $keyId, $privateKey);

    echo "Apple Client Secret Generated Successfully!\n";
    echo "==========================================\n";
    echo 'Client Secret: '.$clientSecret."\n";
    echo 'Expires: '.date('Y-m-d H:i:s', time() + 86400 * 180)."\n";
    echo "\n";
    echo "Add this to your .env file:\n";
    echo 'APPLE_CLIENT_SECRET='.$clientSecret."\n";

} catch (Exception $e) {
    echo 'Error generating Apple Client Secret: '.$e->getMessage()."\n";
    echo "\n";
    echo "Please check:\n";
    echo "1. Your Team ID is correct\n";
    echo "2. Your Client ID is correct\n";
    echo "3. Your Key ID is correct\n";
    echo "4. Your Private Key is properly formatted\n";
    echo "5. OpenSSL extension is enabled in PHP\n";
}
