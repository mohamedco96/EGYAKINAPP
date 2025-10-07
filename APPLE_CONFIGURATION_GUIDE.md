# Apple Sign-In Environment Configuration Guide

## Required Environment Variables

Add these variables to your `.env` file:

```env
# Apple Sign-In Configuration
APPLE_TEAM_ID=YOUR_TEAM_ID_HERE
APPLE_CLIENT_ID=com.yourcompany.yourapp
APPLE_KEY_ID=YOUR_KEY_ID_HERE
APPLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
MIGTAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBHkwdwIBAQQg+QS+WUK7/Li2ydbF
YOUR_ACTUAL_PRIVATE_KEY_CONTENT_HERE
-----END PRIVATE KEY-----"
APPLE_REDIRECT_URI=https://yourdomain.com/api/auth/social/apple/callback
APPLE_CLIENT_SECRET=YOUR_GENERATED_CLIENT_SECRET_HERE
```

## How to Get Each Value

### 1. APPLE_TEAM_ID
- Go to Apple Developer Console → Account → Membership
- Copy your Team ID (10-character string)

### 2. APPLE_CLIENT_ID
- This is your App ID or Service ID from Apple Developer Console
- Format: `com.yourcompany.yourapp` or `com.yourcompany.yourapp.service`

### 3. APPLE_KEY_ID
- Go to Apple Developer Console → Keys
- Find your "Sign In with Apple" key
- Copy the Key ID (10-character string)

### 4. APPLE_PRIVATE_KEY
- Download the `.p8` file from Apple Developer Console
- Open the file and copy the entire content including headers
- Keep the newlines as `\n` in the environment variable

### 5. APPLE_CLIENT_SECRET
- This is a JWT token you need to generate
- Use the script below to generate it

## Generating Apple Client Secret

The Apple Client Secret is a JWT token that needs to be generated using your private key. Here's a PHP script to generate it:

```php
<?php
// Generate Apple Client Secret
function generateAppleClientSecret($teamId, $clientId, $keyId, $privateKey) {
    $header = [
        'alg' => 'ES256',
        'kid' => $keyId
    ];
    
    $payload = [
        'iss' => $teamId,
        'iat' => time(),
        'exp' => time() + 86400 * 180, // 6 months
        'aud' => 'https://appleid.apple.com',
        'sub' => $clientId
    ];
    
    $headerEncoded = base64url_encode(json_encode($header));
    $payloadEncoded = base64url_encode(json_encode($payload));
    
    $signature = '';
    openssl_sign(
        $headerEncoded . '.' . $payloadEncoded,
        $signature,
        $privateKey,
        OPENSSL_ALGO_SHA256
    );
    
    $signatureEncoded = base64url_encode($signature);
    
    return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Usage
$teamId = 'YOUR_TEAM_ID';
$clientId = 'com.yourcompany.yourapp';
$keyId = 'YOUR_KEY_ID';
$privateKey = "-----BEGIN PRIVATE KEY-----\nYOUR_PRIVATE_KEY_CONTENT\n-----END PRIVATE KEY-----";

$clientSecret = generateAppleClientSecret($teamId, $clientId, $keyId, $privateKey);
echo "Apple Client Secret: " . $clientSecret;
?>
```

## Important Notes

1. **Client Secret Expiration**: Apple Client Secrets expire after 6 months and need to be regenerated
2. **Private Key Security**: Keep your private key secure and never commit it to version control
3. **Redirect URI**: Must exactly match what you configured in Apple Developer Console
4. **Domain Verification**: You need to verify your domain in Apple Developer Console

## Testing Your Configuration

Once you have all the values, you can test the Apple Sign-In integration using:

```bash
curl -X POST http://yourdomain.com/api/auth/social/apple \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"identity_token": "test_token"}'
```

## Troubleshooting

- **Invalid Client**: Check that your Client ID matches exactly
- **Invalid Key**: Verify your Key ID and Private Key are correct
- **Expired Secret**: Regenerate your Client Secret if it's expired
- **Domain Mismatch**: Ensure your redirect URI matches Apple's configuration
