# User Endpoints Testing Guide

This guide provides comprehensive information about testing all user-related endpoints in the EGYAKIN application.

## 📋 Overview

The user endpoints test suite covers all authentication, profile management, and user-related functionality including:

- **Authentication**: Registration, login, logout, password management
- **Profile Management**: Profile updates, image uploads, user data management  
- **Localization**: User language preferences and localized responses
- **Password Reset**: Forgot password flow, token verification, password reset
- **Email Verification**: Email verification, OTP generation and validation
- **Notifications**: Localized notifications, read/unread status, filtering

## 🗂️ Test Structure

```
tests/Feature/Modules/Auth/
├── AuthControllerTest.php              # Core authentication tests
├── UserLocaleControllerTest.php        # User locale/language tests  
├── PasswordResetTest.php               # Password reset flow tests
├── EmailVerificationTest.php           # Email verification tests
└── LocalizedNotificationControllerTest.php # Notification tests

tests/Feature/
└── UserEndpointsTestSuite.php          # Test suite runner
```

## 🚀 Running Tests

### Quick Start

```bash
# Run all user endpoint tests
php run_user_tests.php

# Run with verbose output
php run_user_tests.php --verbose

# Run specific test class
php run_user_tests.php --specific=AuthControllerTest

# Generate code coverage report
php run_user_tests.php --coverage
```

### Using PHPUnit Directly

```bash
# Run all user tests
./vendor/bin/phpunit tests/Feature/Modules/Auth/

# Run specific test file
./vendor/bin/phpunit tests/Feature/Modules/Auth/AuthControllerTest.php

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage tests/Feature/Modules/Auth/
```

## 📝 Test Categories

### 1. Authentication Tests (`AuthControllerTest.php`)

#### Registration Tests
- ✅ User registration with valid data
- ✅ Registration validation (required fields)
- ✅ Duplicate email prevention
- ✅ Password confirmation validation

#### Login Tests  
- ✅ Login with valid credentials
- ✅ Login rejection with invalid credentials
- ✅ Login validation (email/password required)
- ✅ Rate limiting for failed attempts

#### Profile Management Tests
- ✅ Get user profile by ID
- ✅ Update user profile
- ✅ Update user by admin (updateUserById)
- ✅ Get all users (index)
- ✅ Show another user's profile
- ✅ Get doctor's patients
- ✅ Get doctor's score history

#### Security Tests
- ✅ Password change with valid current password
- ✅ Password change validation
- ✅ Password change with wrong current password
- ✅ Authentication required for protected endpoints

#### File Upload Tests
- ✅ Profile image upload
- ✅ Syndicate card upload
- ✅ File validation (type, size)

#### User Management Tests
- ✅ User deletion
- ✅ FCM token storage
- ✅ User logout

### 2. Locale Tests (`UserLocaleControllerTest.php`)

#### Locale Management
- ✅ Update user locale (en, ar, fr, es)
- ✅ Get current user locale
- ✅ Locale validation
- ✅ Locale persistence across requests
- ✅ Default locale for new users
- ✅ Locale test endpoint

### 3. Password Reset Tests (`PasswordResetTest.php`)

#### Reset Flow Tests
- ✅ Password reset request
- ✅ Reset token verification
- ✅ Password reset with valid token
- ✅ Invalid/expired token handling
- ✅ Multiple reset requests handling

#### Security Tests
- ✅ Password strength validation
- ✅ Token expiration
- ✅ Email verification requirement
- ✅ Request validation

### 4. Email Verification Tests (`EmailVerificationTest.php`)

#### Email Verification Flow
- ✅ Send verification email
- ✅ Email verification with token
- ✅ Already verified user handling
- ✅ Non-existent email handling

#### OTP Tests
- ✅ OTP generation and sending
- ✅ OTP verification
- ✅ Invalid OTP rejection
- ✅ Expired OTP handling
- ✅ OTP resending
- ✅ Rate limiting for OTP attempts
- ✅ Old OTP invalidation

### 5. Notification Tests (`LocalizedNotificationControllerTest.php`)

#### Notification Management
- ✅ Get all localized notifications
- ✅ Get new (unread) notifications only
- ✅ Mark notification as read
- ✅ Mark all notifications as read
- ✅ Notification pagination
- ✅ Notification filtering by type

#### Localization Tests
- ✅ Localized content based on user locale
- ✅ Fallback to original content
- ✅ Notifications without localization key

#### Security Tests
- ✅ Prevent access to other users' notifications
- ✅ Authentication required
- ✅ Proper authorization checks

## 🔧 Test Setup Requirements

### Database Setup

```bash
# Create test database
php artisan migrate --env=testing

# Seed test data if needed
php artisan db:seed --env=testing
```

### Environment Configuration

Ensure your `.env.testing` file has:

```env
APP_ENV=testing
DB_CONNECTION=mysql
DB_DATABASE=egyakin_test
QUEUE_CONNECTION=sync
MAIL_MAILER=array
```

### Required Factories

The following factories are used in tests:

- `UserFactory` - Creates test users
- `PatientsFactory` - Creates test patients  
- `NotificationFactory` - Creates test notifications
- `ContactFactory` - Creates test contacts

## 📊 Test Coverage

### API Endpoints Covered

#### Public Endpoints
- `POST /api/v1/register` - User registration
- `POST /api/v1/login` - User login
- `POST /api/v1/forgotpassword` - Password reset request
- `POST /api/v1/resetpasswordverification` - Reset token verification
- `POST /api/v1/resetpassword` - Password reset
- `POST /api/v1/email/verification-notification` - Send verification email
- `POST /api/v1/email/verify` - Verify email

#### Protected Endpoints (require authentication)
- `GET /api/v1/users` - Get all users
- `GET /api/v1/users/{id}` - Get user by ID
- `GET /api/v1/showAnotherProfile/{id}` - Show another user's profile
- `GET /api/v1/doctorProfileGetPatients/{id}` - Get doctor's patients
- `GET /api/v1/doctorProfileGetScoreHistory/{id}` - Get doctor's score history
- `PUT /api/v1/users` - Update current user profile
- `PUT /api/v1/users/{id}` - Update user by ID (admin)
- `DELETE /api/v1/users/{id}` - Delete user
- `POST /api/v1/logout` - User logout
- `POST /api/v1/changePassword` - Change password
- `POST /api/v1/upload-profile-image` - Upload profile image
- `POST /api/v1/uploadSyndicateCard` - Upload syndicate card
- `POST /api/v1/storeFCM` - Store FCM token
- `POST /api/v1/user/locale` - Update user locale
- `GET /api/v1/user/locale` - Get user locale
- `GET /api/v1/user/locale/test` - Test locale response
- `POST /api/v1/sendverificationmail` - Send OTP for verification
- `POST /api/v1/emailverification` - Verify email with OTP
- `POST /api/v1/resendemailverification` - Resend OTP
- `GET /api/v1/notifications/localized` - Get localized notifications
- `GET /api/v1/notifications/localized/new` - Get new notifications
- `POST /api/v1/notifications/localized/{id}/read` - Mark notification as read
- `POST /api/v1/notifications/localized/read-all` - Mark all as read

## 🐛 Troubleshooting

### Common Issues

#### 1. Database Connection Errors
```bash
# Solution: Ensure test database exists and is properly configured
php artisan migrate --env=testing
```

#### 2. Factory Errors
```bash
# Solution: Ensure all factories are properly defined
# Check database/factories/ directory
```

#### 3. Authentication Errors
```bash
# Solution: Ensure Sanctum is properly configured
# Check config/sanctum.php and routes/api.php
```

#### 4. Validation Errors
```bash
# Solution: Check request validation rules
# Verify test data matches validation requirements
```

### Debug Tips

1. **Use `--verbose` flag** for detailed test output
2. **Check test database** state between tests
3. **Verify factory data** matches model requirements
4. **Test individual methods** to isolate issues
5. **Check logs** in `storage/logs/` for detailed errors

## 📈 Extending Tests

### Adding New Test Cases

1. **Create test method** following naming convention:
   ```php
   /** @test */
   public function it_can_perform_specific_action()
   {
       // Test implementation
   }
   ```

2. **Use proper assertions**:
   ```php
   $response->assertStatus(200)
            ->assertJson(['value' => true])
            ->assertJsonStructure(['data' => ['id', 'name']]);
   ```

3. **Test both success and failure cases**
4. **Include edge cases and validation tests**
5. **Test authentication and authorization**

### Best Practices

- **Use descriptive test names** that explain what is being tested
- **Follow AAA pattern**: Arrange, Act, Assert
- **Use factories** for test data creation
- **Clean up after tests** (RefreshDatabase trait)
- **Test error conditions** as well as success cases
- **Mock external services** when necessary
- **Keep tests independent** and isolated

## 📚 Additional Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Laravel Sanctum Testing](https://laravel.com/docs/sanctum#testing)
- [Factory Documentation](https://laravel.com/docs/database-testing#writing-factories)

---

**Last Updated**: September 2025  
**Version**: 1.0  
**Maintainer**: Development Team
