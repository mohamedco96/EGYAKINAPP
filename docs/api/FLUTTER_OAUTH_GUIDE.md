# EGYAKIN Flutter OAuth Integration Guide

## Overview

This guide explains how to integrate Apple Sign-In and Google Sign-In into your Flutter application for the EGYAKIN platform.

## Table of Contents

1. [Features Implemented](#features-implemented)
2. [Authentication Flow](#authentication-flow)
3. [API Endpoints](#api-endpoints)
4. [Flutter Implementation](#flutter-implementation)
5. [Profile Completion Flow](#profile-completion-flow)
6. [Error Handling](#error-handling)

---

## Features Implemented

### ✅ Backend Features

1. **Social Authentication**
   - Apple Sign-In OAuth
   - Google Sign-In OAuth
   - Automatic user creation
   - Token-based authentication (Laravel Sanctum)

2. **Smart Data Handling**
   - Handles missing name from Apple (uses email username or generates placeholder)
   - Handles missing email from Apple (generates placeholder email)
   - Generates secure random passwords for social users
   - Automatic profile completion tracking

3. **Profile Completion System**
   - `profile_completed` boolean flag
   - Returned in authentication response
   - API endpoint to complete profile
   - Validates required user data

---

## Authentication Flow

### Apple Sign-In Flow

```
┌─────────┐          ┌──────────┐          ┌─────────┐          ┌──────────┐
│ Flutter │          │  Apple   │          │ Backend │          │ Database │
│   App   │          │  OAuth   │          │   API   │          │          │
└────┬────┘          └────┬─────┘          └────┬────┘          └────┬─────┘
     │                    │                     │                     │
     │ 1. Request Sign In │                     │                     │
     ├───────────────────>│                     │                     │
     │                    │                     │                     │
     │ 2. User Authenticates                    │                     │
     │<───────────────────┤                     │                     │
     │                    │                     │                     │
     │ 3. Return Identity Token                 │                     │
     │<───────────────────┤                     │                     │
     │                    │                     │                     │
     │ 4. POST identity_token                   │                     │
     ├──────────────────────────────────────────>│                     │
     │                    │                     │                     │
     │                    │  5. Verify & Create/Find User             │
     │                    │                     ├────────────────────>│
     │                    │                     │                     │
     │                    │  6. User Data       │                     │
     │                    │                     │<────────────────────┤
     │                    │                     │                     │
     │ 7. Return token + profile_completed      │                     │
     │<──────────────────────────────────────────┤                     │
     │                    │                     │                     │
     │ 8. If !profile_completed → Show Form     │                     │
     │                    │                     │                     │
     │ 9. POST /api/auth/social/complete-profile│                     │
     ├──────────────────────────────────────────>│                     │
     │                    │                     │                     │
     │                    │  10. Update profile & set completed=true  │
     │                    │                     ├────────────────────>│
     │                    │                     │                     │
     │ 11. Success response                     │                     │
     │<──────────────────────────────────────────┤                     │
     │                    │                     │                     │
```

---

## API Endpoints

### 1. Apple Sign-In (Mobile)

**Endpoint:** `POST /api/auth/social/apple`

**Request:**
```json
{
  "identity_token": "eyJraWQiOiJXNldjT0tCIiwiYWxnIjoiUlMyNTYifQ..."
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Authentication successful",
  "data": {
    "user": {
      "id": 123,
      "name": "Mohamed",
      "email": "mohamed@icloud.com",
      "profile_completed": false,
      "avatar": null,
      "locale": "en"
    },
    "token": "1|abc123def456...",
    "provider": "apple"
  }
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Apple authentication failed"
}
```

---

### 2. Google Sign-In (Mobile)

**Endpoint:** `POST /api/auth/social/google`

**Request:**
```json
{
  "access_token": "ya29.a0AfH6SMBx..."
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Authentication successful",
  "data": {
    "user": {
      "id": 124,
      "name": "Ahmed Ali",
      "email": "ahmed@gmail.com",
      "profile_completed": false,
      "avatar": "https://lh3.googleusercontent.com/...",
      "locale": "en"
    },
    "token": "2|xyz789ghi012...",
    "provider": "google"
  }
}
```

---

### 3. Complete Profile

**Endpoint:** `POST /api/v2/update`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request:**
```json
{
  "name": "Mohamed Ibrahim",
  "lname": "Abdel Kader",
  "phone": "+201234567890",
  "specialty": "Cardiology",
  "workingplace": "Cairo University Hospital",
  "job": "Consultant",
  "highestdegree": "MD",
  "gender": "male",
  "birth_date": "1990-05-15"
}
```

**Success Response (200):**
```json
{
  "value": true,
  "message": "User updated successfully",
  "data": {
    "profile_completed": true
  }
}
```

**Note:** The `profile_completed` flag will automatically be set to `true` when both `name` and `email` are present and not empty.

---

## Flutter Implementation

### Step 1: Add Dependencies

Add these packages to your `pubspec.yaml`:

```yaml
dependencies:
  flutter:
    sdk: flutter
  sign_in_with_apple: ^5.0.0
  google_sign_in: ^6.1.5
  http: ^1.1.0
  shared_preferences: ^2.2.2
```

### Step 2: Apple Sign-In Implementation

```dart
import 'package:sign_in_with_apple/sign_in_with_apple.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

class AuthService {
  static const String baseUrl = 'https://test.egyakin.com/api';
  
  // Apple Sign-In
  Future<Map<String, dynamic>?> signInWithApple() async {
    try {
      // 1. Request Apple credential
      final credential = await SignInWithApple.getAppleIDCredential(
        scopes: [
          AppleIDAuthorizationScopes.email,
          AppleIDAuthorizationScopes.fullName,
        ],
      );

      // 2. Send identity token to backend
      final response = await http.post(
        Uri.parse('$baseUrl/auth/social/apple'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'identity_token': credential.identityToken,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success']) {
          // 3. Save token
          final token = data['data']['token'];
          await saveToken(token);
          
          // 4. Check if profile is completed
          final profileCompleted = data['data']['user']['profile_completed'];
          
          return {
            'success': true,
            'token': token,
            'user': data['data']['user'],
            'profile_completed': profileCompleted,
          };
        }
      }
      
      return null;
    } catch (e) {
      print('Apple Sign-In Error: $e');
      return null;
    }
  }
  
  // Save token to secure storage
  Future<void> saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }
  
  // Get saved token
  Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('auth_token');
  }
}
```

### Step 3: Google Sign-In Implementation

```dart
import 'package:google_sign_in/google_sign_in.dart';

class AuthService {
  final GoogleSignIn _googleSignIn = GoogleSignIn(
    scopes: ['email', 'profile'],
  );
  
  // Google Sign-In
  Future<Map<String, dynamic>?> signInWithGoogle() async {
    try {
      // 1. Sign in with Google
      final GoogleSignInAccount? googleUser = await _googleSignIn.signIn();
      
      if (googleUser == null) return null; // User cancelled
      
      // 2. Get authentication
      final GoogleSignInAuthentication googleAuth = 
          await googleUser.authentication;
      
      // 3. Send access token to backend
      final response = await http.post(
        Uri.parse('$baseUrl/auth/social/google'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: jsonEncode({
          'access_token': googleAuth.accessToken,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        
        if (data['success']) {
          final token = data['data']['token'];
          await saveToken(token);
          
          final profileCompleted = data['data']['user']['profile_completed'];
          
          return {
            'success': true,
            'token': token,
            'user': data['data']['user'],
            'profile_completed': profileCompleted,
          };
        }
      }
      
      return null;
    } catch (e) {
      print('Google Sign-In Error: $e');
      return null;
    }
  }
}
```

### Step 4: Complete Profile Implementation

```dart
class AuthService {
  // Complete user profile
  Future<bool> completeProfile({
    required String name,
    required String lname,
    required String phone,
    required String specialty,
    required String workingplace,
    required String job,
    required String highestdegree,
    required String gender,
    required String birthDate,
  }) async {
    try {
      final token = await getToken();
      
      if (token == null) return false;
      
      final response = await http.post(
        Uri.parse('$baseUrl/v2/update'),
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
        body: jsonEncode({
          'name': name,
          'lname': lname,
          'phone': phone,
          'specialty': specialty,
          'workingplace': workingplace,
          'job': job,
          'highestdegree': highestdegree,
          'gender': gender,
          'birth_date': birthDate,
        }),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        return data['value'] == true;
      }
      
      return false;
    } catch (e) {
      print('Profile Completion Error: $e');
      return false;
    }
  }
}
```

---

## Profile Completion Flow

### UI Flow Example

```dart
class LoginScreen extends StatelessWidget {
  final AuthService _authService = AuthService();
  
  Future<void> handleAppleSignIn(BuildContext context) async {
    // Show loading
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => Center(child: CircularProgressIndicator()),
    );
    
    // Sign in with Apple
    final result = await _authService.signInWithApple();
    
    // Hide loading
    Navigator.of(context).pop();
    
    if (result != null && result['success']) {
      if (!result['profile_completed']) {
        // Navigate to profile completion screen
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => CompleteProfileScreen(
              user: result['user'],
            ),
          ),
        );
      } else {
        // Navigate to home screen
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => HomeScreen()),
        );
      }
    } else {
      // Show error
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Sign in failed')),
      );
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            // Apple Sign-In Button
            SignInWithAppleButton(
              onPressed: () => handleAppleSignIn(context),
            ),
            
            SizedBox(height: 20),
            
            // Google Sign-In Button
            ElevatedButton(
              onPressed: () async {
                final result = await _authService.signInWithGoogle();
                // Handle result similar to Apple
              },
              child: Text('Sign in with Google'),
            ),
          ],
        ),
      ),
    );
  }
}
```

### Complete Profile Screen Example

```dart
class CompleteProfileScreen extends StatefulWidget {
  final Map<String, dynamic> user;
  
  const CompleteProfileScreen({Key? key, required this.user}) : super(key: key);
  
  @override
  _CompleteProfileScreenState createState() => _CompleteProfileScreenState();
}

class _CompleteProfileScreenState extends State<CompleteProfileScreen> {
  final _formKey = GlobalKey<FormState>();
  final AuthService _authService = AuthService();
  
  late TextEditingController _nameController;
  late TextEditingController _lnameController;
  late TextEditingController _phoneController;
  // Add more controllers...
  
  @override
  void initState() {
    super.initState();
    _nameController = TextEditingController(text: widget.user['name']);
    _lnameController = TextEditingController();
    _phoneController = TextEditingController();
  }
  
  Future<void> submitProfile() async {
    if (_formKey.currentState!.validate()) {
      // Show loading
      showDialog(
        context: context,
        barrierDismissible: false,
        builder: (context) => Center(child: CircularProgressIndicator()),
      );
      
      // Complete profile
      final success = await _authService.completeProfile(
        name: _nameController.text,
        lname: _lnameController.text,
        phone: _phoneController.text,
        specialty: _specialtyController.text,
        workingplace: _workingplaceController.text,
        job: _jobController.text,
        highestdegree: _highestdegreeController.text,
        gender: _selectedGender,
        birthDate: _selectedDate.toString().split(' ')[0],
      );
      
      // Hide loading
      Navigator.of(context).pop();
      
      if (success) {
        // Navigate to home
        Navigator.pushReplacement(
          context,
          MaterialPageRoute(builder: (context) => HomeScreen()),
        );
      } else {
        // Show error
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to complete profile')),
        );
      }
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Complete Your Profile')),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: EdgeInsets.all(16),
          children: [
            Text(
              'Please complete your profile to continue',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 20),
            
            TextFormField(
              controller: _nameController,
              decoration: InputDecoration(labelText: 'First Name *'),
              validator: (value) => value!.isEmpty ? 'Required' : null,
            ),
            
            TextFormField(
              controller: _lnameController,
              decoration: InputDecoration(labelText: 'Last Name *'),
              validator: (value) => value!.isEmpty ? 'Required' : null,
            ),
            
            TextFormField(
              controller: _phoneController,
              decoration: InputDecoration(labelText: 'Phone *'),
              validator: (value) => value!.isEmpty ? 'Required' : null,
            ),
            
            // Add more fields...
            
            SizedBox(height: 30),
            
            ElevatedButton(
              onPressed: submitProfile,
              child: Text('Complete Profile'),
            ),
          ],
        ),
      ),
    );
  }
}
```

---

## Error Handling

### Common Errors

1. **Apple Sign-In Cancelled**
```dart
if (result == null) {
  // User cancelled sign-in
  print('User cancelled Apple Sign-In');
}
```

2. **Invalid Token**
```json
{
  "success": false,
  "message": "Invalid Apple identity token"
}
```

3. **Network Error**
```dart
try {
  final result = await signInWithApple();
} catch (e) {
  if (e is SocketException) {
    // No internet connection
  } else if (e is TimeoutException) {
    // Request timeout
  }
}
```

4. **Profile Completion Validation Error**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "phone": ["The phone field is required."]
  }
}
```

---

## Testing

### Test Credentials

- **Development URL:** `https://test.egyakin.com`
- **Apple Client ID:** `com.egyakin.app.signin.dev`
- **Google Client ID:** Configure in Google Cloud Console

### Testing Checklist

- [ ] Apple Sign-In works
- [ ] Google Sign-In works
- [ ] Token is saved correctly
- [ ] Profile completion flow works
- [ ] App navigates correctly after completion
- [ ] Error messages are displayed
- [ ] Offline scenario handled

---

## Important Notes

1. **profile_completed Flag**
   - Always check this flag after authentication
   - Force user to complete profile if `false`
   - User cannot proceed to main app without completing profile

2. **Token Management**
   - Store token securely (use flutter_secure_storage for production)
   - Include token in all authenticated requests
   - Handle token expiration

3. **Apple Privacy**
   - Apple may not provide email on subsequent logins
   - Backend handles this automatically
   - User can update email later in profile

4. **Required Fields**
   - All fields in complete-profile endpoint are optional
   - But encourage users to fill important fields
   - You can enforce required fields in your Flutter app

---

## Support

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Test endpoints with Postman
- Verify token in headers
- Check database for user record

---

**Last Updated:** October 8, 2025
**Version:** 1.0
**Backend:** Laravel 10 + Sanctum
**Frontend:** Flutter 3.x+

