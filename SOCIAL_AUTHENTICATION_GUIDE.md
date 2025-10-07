# Social Authentication Implementation Guide

This document provides a comprehensive guide for implementing social authentication with Gmail (Google) and Apple Sign-In in the EGYAKIN Laravel application.

## Overview

The social authentication system has been implemented using Laravel Socialite and supports both web-based OAuth flows and API-based authentication for mobile applications.

## Features Implemented

### ✅ Completed Features

1. **Laravel Socialite Integration**
   - Installed Laravel Socialite v5.23.0
   - Installed Apple Sign-In provider (socialiteproviders/apple v5.7.0)

2. **Database Schema Updates**
   - Added migration for social authentication fields
   - Fields: `google_id`, `apple_id`, `avatar`, `social_verified_at`

3. **User Model Enhancements**
   - Updated fillable fields for social authentication
   - Added helper methods for social user management
   - Added casting for `social_verified_at` timestamp

4. **Social Authentication Controller**
   - Complete implementation for Google and Apple authentication
   - Support for both web OAuth flows and API authentication
   - Proper error handling and logging
   - User creation and linking functionality

5. **Configuration Setup**
   - Updated `config/services.php` with Google and Apple OAuth settings
   - Environment variables configuration

6. **API Routes**
   - Web-based OAuth flows: `/auth/social/google`, `/auth/social/apple`
   - API-based authentication: `POST /auth/social/google`, `POST /auth/social/apple`
   - Callback routes for web flows

## API Endpoints

### Web-based OAuth Flows (for web applications)

```
GET /api/auth/social/google
GET /api/auth/social/google/callback
GET /api/auth/social/apple
GET /api/auth/social/apple/callback
```

### API-based Authentication (for mobile applications)

```
POST /api/auth/social/google
POST /api/auth/social/apple
```

## Environment Configuration

Add the following variables to your `.env` file:

```env
# Google OAuth Configuration
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=http://your-domain.com/api/auth/social/google/callback

# Apple Sign-In Configuration
APPLE_CLIENT_ID=your_apple_client_id
APPLE_CLIENT_SECRET=your_apple_client_secret
APPLE_REDIRECT_URI=http://your-domain.com/api/auth/social/apple/callback
APPLE_TEAM_ID=your_apple_team_id
APPLE_KEY_ID=your_apple_key_id
APPLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nYour_Apple_Private_Key\n-----END PRIVATE KEY-----"
```

## Setup Instructions

### 1. Google OAuth Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable Google+ API
4. Go to "Credentials" → "Create Credentials" → "OAuth 2.0 Client IDs"
5. Configure authorized redirect URIs:
   - `http://your-domain.com/api/auth/social/google/callback`
6. Copy Client ID and Client Secret to your `.env` file

### 2. Apple Sign-In Setup

1. Go to [Apple Developer Console](https://developer.apple.com/)
2. Navigate to "Certificates, Identifiers & Profiles"
3. Create a new App ID with "Sign In with Apple" capability
4. Create a Service ID for web authentication
5. Generate a private key for "Sign In with Apple"
6. Configure redirect URLs and domains
7. Copy the necessary credentials to your `.env` file

### 3. Database Migration

Run the migration to add social authentication fields:

```bash
php artisan migrate
```

## Usage Examples

### Mobile App Integration (API-based)

#### Google Authentication

```javascript
// Frontend (React Native/Flutter)
const googleAuth = async () => {
  try {
    // Get Google access token from Google Sign-In SDK
    const { accessToken } = await GoogleSignin.signIn();
    
    // Send to your API
    const response = await fetch('/api/auth/social/google', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        access_token: accessToken
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Store the token and user data
      localStorage.setItem('auth_token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
    }
  } catch (error) {
    console.error('Google authentication failed:', error);
  }
};
```

#### Apple Authentication

```javascript
// Frontend (React Native/Flutter)
const appleAuth = async () => {
  try {
    // Get Apple identity token from Apple Sign-In SDK
    const { identityToken } = await AppleAuthentication.signIn();
    
    // Send to your API
    const response = await fetch('/api/auth/social/apple', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        identity_token: identityToken
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Store the token and user data
      localStorage.setItem('auth_token', data.data.token);
      localStorage.setItem('user', JSON.stringify(data.data.user));
    }
  } catch (error) {
    console.error('Apple authentication failed:', error);
  }
};
```

### Web Application Integration

#### Google OAuth Flow

```html
<!-- Frontend (Web) -->
<a href="/api/auth/social/google" class="google-signin-btn">
  Sign in with Google
</a>
```

#### Apple OAuth Flow

```html
<!-- Frontend (Web) -->
<a href="/api/auth/social/apple" class="apple-signin-btn">
  Sign in with Apple
</a>
```

## API Response Format

### Success Response

```json
{
  "success": true,
  "message": "Authentication successful",
  "data": {
    "user": {
      "id": 123,
      "name": "John Doe",
      "email": "john@example.com",
      "avatar": "https://example.com/avatar.jpg",
      "locale": "en"
    },
    "token": "sanctum_token_here",
    "provider": "google"
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Authentication failed"
}
```

## Security Considerations

1. **Token Validation**: The system validates tokens from both Google and Apple
2. **User Blocking**: Blocked users cannot authenticate via social providers
3. **Account Linking**: Existing users can link social accounts to their profiles
4. **Rate Limiting**: Consider implementing rate limiting for authentication endpoints
5. **Logging**: All authentication attempts are logged for security monitoring

## Testing

### Test Google Authentication

```bash
curl -X POST http://your-domain.com/api/auth/social/google \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"access_token": "your_google_access_token"}'
```

### Test Apple Authentication

```bash
curl -X POST http://your-domain.com/api/auth/social/apple \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"identity_token": "your_apple_identity_token"}'
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**: Ensure your database is running and accessible
2. **OAuth Configuration**: Verify all environment variables are correctly set
3. **Redirect URI Mismatch**: Ensure redirect URIs match exactly in OAuth provider settings
4. **Token Validation**: Check that tokens are properly formatted and not expired

### Debug Mode

Enable debug logging by setting `LOG_LEVEL=debug` in your `.env` file to see detailed authentication logs.

## Next Steps

1. **Run Migration**: Execute `php artisan migrate` when database is available
2. **Configure OAuth Providers**: Set up Google and Apple OAuth applications
3. **Update Environment Variables**: Add all required OAuth credentials
4. **Test Integration**: Test both web and mobile authentication flows
5. **Frontend Integration**: Implement social login buttons in your frontend applications

## Support

For issues or questions regarding the social authentication implementation, refer to:
- [Laravel Socialite Documentation](https://laravel.com/docs/socialite)
- [Google OAuth Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Apple Sign-In Documentation](https://developer.apple.com/sign-in-with-apple/)
