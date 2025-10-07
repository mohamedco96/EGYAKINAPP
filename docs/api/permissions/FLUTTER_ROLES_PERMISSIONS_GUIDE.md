# Flutter Roles & Permissions Integration Guide

## Overview

This guide explains how to implement and manage roles and permissions in your Flutter application for the EGYAKIN system. The backend uses **Spatie Laravel Permission** package with Laravel Sanctum for authentication.

## Table of Contents
- [Backend Architecture](#backend-architecture)
- [Available API Endpoints](#available-api-endpoints)
- [Flutter Implementation](#flutter-implementation)
- [Data Models](#data-models)
- [Permission Checking Strategy](#permission-checking-strategy)
- [UI Conditional Rendering](#ui-conditional-rendering)
- [Best Practices](#best-practices)
- [Error Handling](#error-handling)

---

## Backend Architecture

### Technology Stack
- **Package**: Spatie Laravel Permission
- **Authentication**: Laravel Sanctum (Token-based)
- **Database Tables**:
  - `roles` - Stores role definitions
  - `permissions` - Stores permission definitions
  - `model_has_roles` - Links users to roles
  - `model_has_permissions` - Links users to direct permissions
  - `role_has_permissions` - Links roles to permissions

### Permission Categories
The system organizes permissions into categories:
- `users` - User Management
- `roles` - Role Management
- `posts` - Content Management
- `reports` - Reports & Analytics
- `settings` - System Settings
- `other` - Other permissions

### Key Models
- **User Model**: Uses `HasRoles` and `HasPermissions` traits
- **Permission Model**: Extended from Spatie with categories
- **Role Model**: Standard Spatie role model

---

## Available API Endpoints

### 1. Authentication Endpoints

#### Login
```http
POST /api/login
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "password123",
  "fcmToken": "optional_fcm_token",
  "deviceId": "optional_device_id",
  "deviceType": "ios|android",
  "appVersion": "1.0.0"
}
```

**Response (Success):**
```json
{
  "value": true,
  "message": "User logged in successfully",
  "token": "1|sanctum_token_here",
  "data": {
    "id": 1,
    "name": "John",
    "lname": "Doe",
    "email": "user@example.com",
    "image": "https://...",
    "specialty": "Cardiology",
    // ... other user fields
  }
}
```

**Note:** The current login response does NOT include roles and permissions. You need to fetch them separately or modify the backend.

#### Get Current User
```http
GET /api/user
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "name": "John",
  "email": "user@example.com",
  // Standard user fields
  // Note: Does not include roles/permissions by default
}
```

### 2. Role & Permission Endpoints

#### Check User Permissions
```http
POST /api/checkPermission
Authorization: Bearer {token}
```

**Request Body:** None required

**Response (Admin Role):**
```json
{
  "value": true,
  "message": "user have admin role"
}
```

**Response (Specific Permission):**
```json
{
  "value": true,
  "message": "User has permission to delete patient"
}
```

**Response (No Permission):**
```json
{
  "value": false,
  "message": "User does not have permission to edit articles"
}
```

**Note:** This endpoint currently only checks for:
- Admin role
- Delete patient permission
It needs to be enhanced for more flexible permission checking.

#### Assign Role to User
```http
POST /api/assignRoleToUser
Authorization: Bearer {token}
```

**Request Body (Assign Role):**
```json
{
  "action": "assign_role",
  "roleOrPermission": "admin"
}
```

**Request Body (Assign Permission):**
```json
{
  "action": "assign_permission",
  "roleOrPermission": "delete patient"
}
```

**Response:**
```json
{
  "value": true,
  "message": "Role assigned to user successfully!"
}
```

#### Create Role or Permission
```http
POST /api/createRoleAndPermission
Authorization: Bearer {token}
```

**Request Body (Create Role):**
```json
{
  "action": "create_role",
  "role": "moderator"
}
```

**Request Body (Create Permission):**
```json
{
  "action": "create_permission",
  "permission": "edit posts"
}
```

**Request Body (Assign Permission to Role):**
```json
{
  "action": "assign_permission",
  "role": "moderator",
  "permission": "edit posts"
}
```

---

## Flutter Implementation

### 1. Data Models

Create Flutter models to represent user permissions:

```dart
// models/user_permission.dart
class UserPermissions {
  final List<String> roles;
  final List<String> permissions;
  
  UserPermissions({
    required this.roles,
    required this.permissions,
  });
  
  factory UserPermissions.fromJson(Map<String, dynamic> json) {
    return UserPermissions(
      roles: json['roles'] != null 
          ? List<String>.from(json['roles'].map((r) => r['name'] ?? r))
          : [],
      permissions: json['permissions'] != null
          ? List<String>.from(json['permissions'].map((p) => p['name'] ?? p))
          : [],
    );
  }
  
  Map<String, dynamic> toJson() {
    return {
      'roles': roles,
      'permissions': permissions,
    };
  }
  
  bool hasRole(String role) {
    return roles.contains(role);
  }
  
  bool hasPermission(String permission) {
    return permissions.contains(permission);
  }
  
  bool hasAnyRole(List<String> roleList) {
    return roleList.any((role) => roles.contains(role));
  }
  
  bool hasAnyPermission(List<String> permissionList) {
    return permissionList.any((perm) => permissions.contains(perm));
  }
  
  bool hasAllPermissions(List<String> permissionList) {
    return permissionList.every((perm) => permissions.contains(perm));
  }
  
  bool get isAdmin => hasRole('admin');
  bool get isModerator => hasRole('moderator');
}
```

```dart
// models/user.dart
class User {
  final int id;
  final String name;
  final String? lname;
  final String email;
  final String? image;
  final String? specialty;
  final UserPermissions? permissions;
  // ... other fields
  
  User({
    required this.id,
    required this.name,
    this.lname,
    required this.email,
    this.image,
    this.specialty,
    this.permissions,
  });
  
  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      lname: json['lname'],
      email: json['email'],
      image: json['image'],
      specialty: json['specialty'],
      permissions: json['roles'] != null || json['permissions'] != null
          ? UserPermissions.fromJson(json)
          : null,
    );
  }
  
  String get fullName => lname != null ? '$name $lname' : name;
}
```

### 2. Permission Service

Create a service to manage permissions:

```dart
// services/permission_service.dart
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import '../models/user_permission.dart';
import 'api_service.dart';

class PermissionService {
  static const String _permissionsKey = 'user_permissions';
  final ApiService _apiService;
  
  PermissionService(this._apiService);
  
  // Cache permissions locally
  Future<void> cachePermissions(UserPermissions permissions) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_permissionsKey, jsonEncode(permissions.toJson()));
  }
  
  // Get cached permissions
  Future<UserPermissions?> getCachedPermissions() async {
    final prefs = await SharedPreferences.getInstance();
    final cached = prefs.getString(_permissionsKey);
    if (cached != null) {
      return UserPermissions.fromJson(jsonDecode(cached));
    }
    return null;
  }
  
  // Clear cached permissions (on logout)
  Future<void> clearPermissions() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_permissionsKey);
  }
  
  // Fetch permissions from API
  // Note: You'll need to enhance the backend to return permissions
  Future<UserPermissions?> fetchUserPermissions() async {
    try {
      // Option 1: Use the check permission endpoint (limited functionality)
      final response = await _apiService.post('/checkPermission');
      
      // Option 2: Fetch user data with roles/permissions included
      // This requires backend modification to include roles/permissions
      // in the /api/user endpoint response
      
      // For now, using Option 1:
      if (response['value'] == true) {
        // Parse based on message (this is temporary until backend is enhanced)
        List<String> roles = [];
        List<String> permissions = [];
        
        if (response['message'].contains('admin role')) {
          roles.add('admin');
        }
        if (response['message'].contains('delete patient')) {
          permissions.add('delete patient');
        }
        
        final userPermissions = UserPermissions(
          roles: roles,
          permissions: permissions,
        );
        
        await cachePermissions(userPermissions);
        return userPermissions;
      }
      
      return null;
    } catch (e) {
      print('Error fetching permissions: $e');
      // Return cached permissions as fallback
      return await getCachedPermissions();
    }
  }
  
  // Check if user has specific role
  Future<bool> hasRole(String role) async {
    final permissions = await getCachedPermissions();
    return permissions?.hasRole(role) ?? false;
  }
  
  // Check if user has specific permission
  Future<bool> hasPermission(String permission) async {
    final permissions = await getCachedPermissions();
    return permissions?.hasPermission(permission) ?? false;
  }
  
  // Check if user is admin
  Future<bool> isAdmin() async {
    return await hasRole('admin');
  }
}
```

### 3. Authentication Service Enhancement

Enhance your authentication service to handle permissions:

```dart
// services/auth_service.dart
import '../models/user.dart';
import '../models/user_permission.dart';
import 'api_service.dart';
import 'permission_service.dart';

class AuthService {
  final ApiService _apiService;
  final PermissionService _permissionService;
  
  AuthService(this._apiService, this._permissionService);
  
  Future<User?> login(String email, String password) async {
    try {
      final response = await _apiService.post('/login', {
        'email': email,
        'password': password,
      });
      
      if (response['value'] == true) {
        // Save token
        await _apiService.saveToken(response['token']);
        
        // Parse user data
        final user = User.fromJson(response['data']);
        
        // Fetch and cache permissions
        await _permissionService.fetchUserPermissions();
        
        return user;
      }
      
      return null;
    } catch (e) {
      print('Login error: $e');
      rethrow;
    }
  }
  
  Future<void> logout() async {
    await _apiService.post('/logout');
    await _apiService.clearToken();
    await _permissionService.clearPermissions();
  }
}
```

### 4. API Service Example

```dart
// services/api_service.dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static const String baseUrl = 'https://your-api-url.com/api';
  String? _token;
  
  Future<void> saveToken(String token) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('auth_token', token);
  }
  
  Future<void> loadToken() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('auth_token');
  }
  
  Future<void> clearToken() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('auth_token');
  }
  
  Future<Map<String, dynamic>> post(String endpoint, [Map<String, dynamic>? body]) async {
    final url = Uri.parse('$baseUrl$endpoint');
    final headers = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
    
    final response = await http.post(
      url,
      headers: headers,
      body: body != null ? jsonEncode(body) : null,
    );
    
    if (response.statusCode == 200 || response.statusCode == 201) {
      return jsonDecode(response.body);
    } else if (response.statusCode == 401) {
      // Token expired or invalid
      await clearToken();
      throw Exception('Unauthorized');
    } else {
      throw Exception('API Error: ${response.statusCode}');
    }
  }
  
  Future<Map<String, dynamic>> get(String endpoint) async {
    final url = Uri.parse('$baseUrl$endpoint');
    final headers = {
      'Accept': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };
    
    final response = await http.get(url, headers: headers);
    
    if (response.statusCode == 200) {
      return jsonDecode(response.body);
    } else if (response.statusCode == 401) {
      await clearToken();
      throw Exception('Unauthorized');
    } else {
      throw Exception('API Error: ${response.statusCode}');
    }
  }
}
```

---

## Permission Checking Strategy

### 1. Real-time Permission Check (Online)

Use this when you need the most up-to-date permissions:

```dart
Future<bool> checkPermissionOnline(String permission) async {
  try {
    final response = await apiService.post('/checkPermission');
    // Parse response based on your needs
    return response['value'] == true;
  } catch (e) {
    // Fallback to cached
    return await permissionService.hasPermission(permission);
  }
}
```

### 2. Cached Permission Check (Offline-first)

Use this for better performance and offline support:

```dart
Future<bool> checkPermissionCached(String permission) async {
  final permissions = await permissionService.getCachedPermissions();
  return permissions?.hasPermission(permission) ?? false;
}
```

### 3. Hybrid Approach (Recommended)

```dart
class PermissionChecker {
  final PermissionService _permissionService;
  final ApiService _apiService;
  
  PermissionChecker(this._permissionService, this._apiService);
  
  Future<bool> hasPermission(String permission, {bool forceRefresh = false}) async {
    if (forceRefresh) {
      await _permissionService.fetchUserPermissions();
    }
    
    final cached = await _permissionService.getCachedPermissions();
    return cached?.hasPermission(permission) ?? false;
  }
  
  Future<bool> hasRole(String role, {bool forceRefresh = false}) async {
    if (forceRefresh) {
      await _permissionService.fetchUserPermissions();
    }
    
    final cached = await _permissionService.getCachedPermissions();
    return cached?.hasRole(role) ?? false;
  }
}
```

---

## UI Conditional Rendering

### 1. Widget-based Permission Check

```dart
// widgets/permission_widget.dart
import 'package:flutter/material.dart';
import '../services/permission_service.dart';

class PermissionWidget extends StatelessWidget {
  final String? role;
  final String? permission;
  final Widget child;
  final Widget? fallback;
  final PermissionService permissionService;
  
  const PermissionWidget({
    Key? key,
    this.role,
    this.permission,
    required this.child,
    this.fallback,
    required this.permissionService,
  }) : assert(role != null || permission != null),
       super(key: key);
  
  @override
  Widget build(BuildContext context) {
    return FutureBuilder<bool>(
      future: _checkPermission(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const SizedBox.shrink();
        }
        
        if (snapshot.data == true) {
          return child;
        }
        
        return fallback ?? const SizedBox.shrink();
      },
    );
  }
  
  Future<bool> _checkPermission() async {
    if (role != null) {
      return await permissionService.hasRole(role!);
    }
    if (permission != null) {
      return await permissionService.hasPermission(permission!);
    }
    return false;
  }
}

// Usage:
PermissionWidget(
  permission: 'delete patient',
  permissionService: permissionService,
  child: ElevatedButton(
    onPressed: () => deletePatient(),
    child: Text('Delete Patient'),
  ),
)
```

### 2. Provider-based Approach (Recommended)

Using provider or riverpod for state management:

```dart
// providers/permission_provider.dart
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/user_permission.dart';
import '../services/permission_service.dart';

final permissionProvider = FutureProvider<UserPermissions?>((ref) async {
  final permissionService = ref.read(permissionServiceProvider);
  return await permissionService.getCachedPermissions();
});

final permissionServiceProvider = Provider<PermissionService>((ref) {
  final apiService = ref.read(apiServiceProvider);
  return PermissionService(apiService);
});

// Check specific permission
final hasDeletePatientPermissionProvider = FutureProvider<bool>((ref) async {
  final permissions = await ref.read(permissionProvider.future);
  return permissions?.hasPermission('delete patient') ?? false;
});

// Check admin role
final isAdminProvider = FutureProvider<bool>((ref) async {
  final permissions = await ref.read(permissionProvider.future);
  return permissions?.isAdmin ?? false;
});
```

Usage in widgets:

```dart
class PatientDetailScreen extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final hasDeletePermission = ref.watch(hasDeletePatientPermissionProvider);
    
    return Scaffold(
      appBar: AppBar(
        title: Text('Patient Details'),
        actions: [
          hasDeletePermission.when(
            data: (canDelete) => canDelete
                ? IconButton(
                    icon: Icon(Icons.delete),
                    onPressed: () => deletePatient(),
                  )
                : SizedBox.shrink(),
            loading: () => SizedBox.shrink(),
            error: (_, __) => SizedBox.shrink(),
          ),
        ],
      ),
      body: PatientInfo(),
    );
  }
}
```

### 3. Route Guards

Protect entire screens based on permissions:

```dart
// utils/route_guard.dart
class RouteGuard {
  final PermissionService _permissionService;
  
  RouteGuard(this._permissionService);
  
  Future<bool> canAccess({String? role, String? permission}) async {
    if (role != null) {
      return await _permissionService.hasRole(role);
    }
    if (permission != null) {
      return await _permissionService.hasPermission(permission);
    }
    return false;
  }
}

// In your router (e.g., GoRouter):
GoRoute(
  path: '/admin',
  builder: (context, state) => AdminScreen(),
  redirect: (context, state) async {
    final routeGuard = context.read<RouteGuard>();
    final canAccess = await routeGuard.canAccess(role: 'admin');
    
    if (!canAccess) {
      return '/unauthorized';
    }
    return null;
  },
)
```

---

## Best Practices

### 1. Permission Caching
- Always cache permissions locally after login
- Refresh permissions when the app comes to foreground
- Set a reasonable cache expiry (e.g., 30 minutes)

```dart
class PermissionCache {
  static const Duration cacheExpiry = Duration(minutes: 30);
  DateTime? _lastFetch;
  
  bool get shouldRefresh {
    if (_lastFetch == null) return true;
    return DateTime.now().difference(_lastFetch!) > cacheExpiry;
  }
  
  Future<UserPermissions?> getPermissions(PermissionService service) async {
    if (shouldRefresh) {
      final fresh = await service.fetchUserPermissions();
      _lastFetch = DateTime.now();
      return fresh;
    }
    return await service.getCachedPermissions();
  }
}
```

### 2. Graceful Degradation
- Always provide fallback UI when permissions can't be determined
- Show loading states while checking permissions
- Handle permission errors gracefully

```dart
Widget buildWithPermission({
  required Future<bool> permissionCheck,
  required Widget child,
  Widget? loading,
  Widget? noPermission,
}) {
  return FutureBuilder<bool>(
    future: permissionCheck,
    builder: (context, snapshot) {
      if (snapshot.connectionState == ConnectionState.waiting) {
        return loading ?? CircularProgressIndicator();
      }
      
      if (snapshot.hasError) {
        return noPermission ?? SizedBox.shrink();
      }
      
      if (snapshot.data == true) {
        return child;
      }
      
      return noPermission ?? SizedBox.shrink();
    },
  );
}
```

### 3. Sync Permissions Periodically
```dart
class PermissionSyncService {
  final PermissionService _permissionService;
  Timer? _syncTimer;
  
  PermissionSyncService(this._permissionService);
  
  void startSync() {
    _syncTimer = Timer.periodic(Duration(minutes: 15), (_) {
      _permissionService.fetchUserPermissions();
    });
  }
  
  void stopSync() {
    _syncTimer?.cancel();
  }
}
```

### 4. Optimistic UI Updates
- Show UI based on cached permissions immediately
- Refresh in background
- Handle permission change gracefully

```dart
class PermissionAwareButton extends StatefulWidget {
  final String permission;
  final VoidCallback onPressed;
  final Widget child;
  
  @override
  _PermissionAwareButtonState createState() => _PermissionAwareButtonState();
}

class _PermissionAwareButtonState extends State<PermissionAwareButton> {
  bool _hasPermission = false;
  
  @override
  void initState() {
    super.initState();
    _checkPermission();
  }
  
  Future<void> _checkPermission() async {
    final service = context.read<PermissionService>();
    final hasIt = await service.hasPermission(widget.permission);
    if (mounted) {
      setState(() => _hasPermission = hasIt);
    }
  }
  
  @override
  Widget build(BuildContext context) {
    if (!_hasPermission) return SizedBox.shrink();
    
    return ElevatedButton(
      onPressed: widget.onPressed,
      child: widget.child,
    );
  }
}
```

---

## Error Handling

### 1. Network Errors
```dart
try {
  final permissions = await permissionService.fetchUserPermissions();
} catch (e) {
  if (e is SocketException) {
    // No internet connection
    print('No internet, using cached permissions');
    return await permissionService.getCachedPermissions();
  } else if (e.toString().contains('401')) {
    // Token expired, redirect to login
    navigateToLogin();
  } else {
    // Other errors
    showError('Failed to fetch permissions');
  }
}
```

### 2. Permission Denied Handling
```dart
Future<void> performAction() async {
  final hasPermission = await permissionService.hasPermission('edit posts');
  
  if (!hasPermission) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Permission Denied'),
        content: Text('You don\'t have permission to perform this action.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: Text('OK'),
          ),
        ],
      ),
    );
    return;
  }
  
  // Perform the action
  await editPost();
}
```

---

## Backend Enhancement Recommendations

### 1. Include Permissions in Login Response

Modify the backend `AuthService::login()` method to include roles and permissions:

```php
// app/Modules/Auth/Services/AuthService.php
public function login(array $validatedData): array
{
    // ... existing login logic ...
    
    // Load roles and permissions
    $user->load('roles', 'permissions');
    
    return [
        'value' => true,
        'message' => __('api.user_logged_in_successfully'),
        'token' => $token,
        'data' => $user,
        'roles' => $user->roles->pluck('name'),
        'permissions' => $user->permissions->pluck('name'),
        'status_code' => 200,
    ];
}
```

### 2. Add Flexible Permission Check Endpoint

Create a new endpoint to check multiple permissions:

```php
// app/Modules/RolePermission/Controllers/RolePermissionController.php
public function checkMultiplePermissions(Request $request): JsonResponse
{
    $user = Auth::user();
    $permissionsToCheck = $request->input('permissions', []);
    $rolesToCheck = $request->input('roles', []);
    
    $result = [
        'roles' => [],
        'permissions' => [],
    ];
    
    foreach ($rolesToCheck as $role) {
        $result['roles'][$role] = $user->hasRole($role);
    }
    
    foreach ($permissionsToCheck as $permission) {
        $result['permissions'][$permission] = $user->hasPermissionTo($permission);
    }
    
    return response()->json([
        'value' => true,
        'data' => $result,
    ]);
}
```

### 3. Add Endpoint to Get All User Permissions

```php
public function getUserPermissions(): JsonResponse
{
    $user = Auth::user();
    
    return response()->json([
        'value' => true,
        'data' => [
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'direct_permissions' => $user->permissions->pluck('name'),
        ],
    ]);
}
```

Add to routes:
```php
// routes/api.php
Route::post('/checkMultiplePermissions', [RolePermissionController::class, 'checkMultiplePermissions']);
Route::get('/userPermissions', [RolePermissionController::class, 'getUserPermissions']);
```

---

## Example: Complete Implementation

Here's a complete example showing all pieces together:

```dart
// main.dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  final apiService = ApiService();
  await apiService.loadToken();
  
  final permissionService = PermissionService(apiService);
  final authService = AuthService(apiService, permissionService);
  
  runApp(
    MultiProvider(
      providers: [
        Provider.value(value: apiService),
        Provider.value(value: permissionService),
        Provider.value(value: authService),
      ],
      child: MyApp(),
    ),
  );
}

// screens/login_screen.dart
class LoginScreen extends StatelessWidget {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  
  @override
  Widget build(BuildContext context) {
    final authService = context.read<AuthService>();
    
    return Scaffold(
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            TextField(
              controller: _emailController,
              decoration: InputDecoration(labelText: 'Email'),
            ),
            SizedBox(height: 16),
            TextField(
              controller: _passwordController,
              decoration: InputDecoration(labelText: 'Password'),
              obscureText: true,
            ),
            SizedBox(height: 24),
            ElevatedButton(
              onPressed: () async {
                try {
                  final user = await authService.login(
                    _emailController.text,
                    _passwordController.text,
                  );
                  
                  if (user != null) {
                    Navigator.pushReplacementNamed(context, '/home');
                  }
                } catch (e) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(content: Text('Login failed: $e')),
                  );
                }
              },
              child: Text('Login'),
            ),
          ],
        ),
      ),
    );
  }
}

// screens/home_screen.dart
class HomeScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final permissionService = context.read<PermissionService>();
    
    return Scaffold(
      appBar: AppBar(
        title: Text('Home'),
      ),
      body: ListView(
        children: [
          FutureBuilder<bool>(
            future: permissionService.hasPermission('delete patient'),
            builder: (context, snapshot) {
              if (snapshot.data == true) {
                return ListTile(
                  leading: Icon(Icons.delete),
                  title: Text('Delete Patient'),
                  onTap: () => Navigator.pushNamed(context, '/patients/delete'),
                );
              }
              return SizedBox.shrink();
            },
          ),
          FutureBuilder<bool>(
            future: permissionService.isAdmin(),
            builder: (context, snapshot) {
              if (snapshot.data == true) {
                return ListTile(
                  leading: Icon(Icons.admin_panel_settings),
                  title: Text('Admin Panel'),
                  onTap: () => Navigator.pushNamed(context, '/admin'),
                );
              }
              return SizedBox.shrink();
            },
          ),
        ],
      ),
    );
  }
}
```

---

## Summary

1. **Backend uses** Spatie Laravel Permission with Sanctum authentication
2. **Current limitations**: Login doesn't return roles/permissions, need separate fetch
3. **Recommended approach**: 
   - Cache permissions locally after login
   - Use offline-first strategy with periodic refresh
   - Implement permission-based widgets for conditional rendering
4. **Backend enhancements needed**:
   - Include permissions in login response
   - Add flexible permission check endpoint
   - Add endpoint to get all user permissions

For questions or issues, refer to the backend documentation or contact the development team.

