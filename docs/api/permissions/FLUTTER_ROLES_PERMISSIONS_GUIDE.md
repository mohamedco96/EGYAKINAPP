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
POST /api/v2/login
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
    "profile_completed": true,
    "avatar": "https://...",
    "locale": "en"
  },
  "roles": ["doctor"],
  "permissions": [
    "view-patients",
    "create-patients",
    "edit-patients",
    "view-posts",
    "create-posts",
    "use-ai-consultation",
    "view-consultations",
    "create-consultations"
  ],
  "status_code": 200
}
```

**✅ Enhanced:** The login response now includes roles and permissions immediately!

#### Get Current User
```http
GET /api/v2/user
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "name": "John",
  "email": "user@example.com",
  "profile_completed": true,
  "avatar": "https://...",
  "locale": "en",
  "roles": ["doctor"],
  "permissions": [
    "view-patients",
    "create-patients",
    "edit-patients",
    "view-posts",
    "create-posts",
    "use-ai-consultation"
  ],
  "created_at": "2025-01-01T00:00:00.000000Z",
  "updated_at": "2025-01-01T00:00:00.000000Z"
}
```

**✅ Enhanced:** The user endpoint now includes roles and permissions for refreshing without re-login!

### 2. Role & Permission Endpoints

#### Check User Permissions
```http
POST /api/v2/checkPermission
Authorization: Bearer {token}
```

**Request Body:** None required

**Response (Enhanced):**
```json
{
  "success": true,
  "data": {
    "value": true,
    "message": "User permissions retrieved successfully",
    "roles": ["doctor"],
    "permissions": [
      "view-patients",
      "create-patients",
      "edit-patients",
      "view-posts",
      "create-posts",
      "use-ai-consultation"
    ],
    "has_admin_role": false,
    "has_super_admin_role": false
  },
  "status_code": 200
}
```

**✅ Enhanced:** Now returns complete roles and permissions list instead of just checking specific permissions!

#### Assign Role to User
```http
POST /api/v2/assignRoleToUser
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_id": 1,
  "role_name": "doctor"
}
```

**Response:**
```json
{
  "value": true,
  "message": "Role assigned to user successfully!"
}
```

#### Create Role and Permission
```http
POST /api/v2/createRoleAndPermission
Authorization: Bearer {token}
Content-Type: application/json

{
  "action": "create_role",
  "role_name": "moderator"
}
```

**Response:**
```json
{
  "value": true,
  "message": "Role created successfully!",
  "role": {
    "id": 1,
    "name": "moderator",
    "guard_name": "web"
  }
}
```

---

## Flutter Implementation

### 1. Data Models

Create models to represent the API responses:

```dart
class User {
  final int id;
  final String name;
  final String email;
  final bool profileCompleted;
  final String? avatar;
  final String locale;
  final List<String> roles;
  final List<String> permissions;
  
  User({
    required this.id,
    required this.name,
    required this.email,
    required this.profileCompleted,
    this.avatar,
    required this.locale,
    required this.roles,
    required this.permissions,
  });
  
  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      email: json['email'],
      profileCompleted: json['profile_completed'] ?? false,
      avatar: json['avatar'],
      locale: json['locale'] ?? 'en',
      roles: List<String>.from(json['roles'] ?? []),
      permissions: List<String>.from(json['permissions'] ?? []),
    );
  }
}

class LoginResponse {
  final bool value;
  final String message;
  final String token;
  final User data;
  final List<String> roles;
  final List<String> permissions;
  
  LoginResponse({
    required this.value,
    required this.message,
    required this.token,
    required this.data,
    required this.roles,
    required this.permissions,
  });
  
  factory LoginResponse.fromJson(Map<String, dynamic> json) {
    return LoginResponse(
      value: json['value'],
      message: json['message'],
      token: json['token'],
      data: User.fromJson(json['data']),
      roles: List<String>.from(json['roles'] ?? []),
      permissions: List<String>.from(json['permissions'] ?? []),
    );
  }
}
```

### 2. API Service

```dart
class AuthService {
  final Dio _dio = Dio();
  
  Future<LoginResponse> login(String email, String password) async {
    try {
      final response = await _dio.post(
        'https://your-api.com/api/v2/login',
        data: {
          'email': email,
          'password': password,
        },
      );
      
      return LoginResponse.fromJson(response.data);
    } catch (e) {
      throw Exception('Login failed: $e');
    }
  }
  
  Future<User> getCurrentUser(String token) async {
    try {
      final response = await _dio.get(
        'https://your-api.com/api/v2/user',
        options: Options(
          headers: {'Authorization': 'Bearer $token'},
        ),
      );
      
      return User.fromJson(response.data);
    } catch (e) {
      throw Exception('Failed to get user: $e');
    }
  }
  
  Future<Map<String, dynamic>> checkPermissions(String token) async {
    try {
      final response = await _dio.post(
        'https://your-api.com/api/v2/checkPermission',
        options: Options(
          headers: {'Authorization': 'Bearer $token'},
        ),
      );
      
      return response.data;
    } catch (e) {
      throw Exception('Failed to check permissions: $e');
    }
  }
}
```

### 3. State Management

```dart
class UserState extends ChangeNotifier {
  User? _user;
  List<String> _roles = [];
  List<String> _permissions = [];
  String? _token;
  
  User? get user => _user;
  List<String> get roles => _roles;
  List<String> get permissions => _permissions;
  String? get token => _token;
  
  bool get isLoggedIn => _user != null && _token != null;
  
  // Permission checking methods
  bool hasPermission(String permission) {
    return _permissions.contains(permission);
  }
  
  bool hasRole(String role) {
    return _roles.contains(role);
  }
  
  bool hasAnyPermission(List<String> permissions) {
    return permissions.any((permission) => _permissions.contains(permission));
  }
  
  bool hasAnyRole(List<String> roles) {
    return roles.any((role) => _roles.contains(role));
  }
  
  // Admin checks
  bool get isAdmin => hasRole('admin');
  bool get isSuperAdmin => hasRole('super-admin');
  bool get isDoctor => hasRole('doctor');
  bool get isModerator => hasRole('moderator');
  
  // Common permission checks
  bool get canViewPatients => hasPermission('view-patients');
  bool get canCreatePatients => hasPermission('create-patients');
  bool get canEditPatients => hasPermission('edit-patients');
  bool get canDeletePatients => hasPermission('delete-patients');
  
  bool get canViewPosts => hasPermission('view-posts');
  bool get canCreatePosts => hasPermission('create-posts');
  bool get canModeratePosts => hasPermission('moderate-posts');
  
  bool get canUseAI => hasPermission('use-ai-consultation');
  bool get canViewConsultations => hasPermission('view-consultations');
  bool get canCreateConsultations => hasPermission('create-consultations');
  
  // Update user data
  void updateUser(User user, List<String> roles, List<String> permissions) {
    _user = user;
    _roles = roles;
    _permissions = permissions;
    notifyListeners();
  }
  
  void updatePermissions(List<String> permissions) {
    _permissions = permissions;
    notifyListeners();
  }
  
  void logout() {
    _user = null;
    _roles = [];
    _permissions = [];
    _token = null;
    notifyListeners();
  }
}
```

### 4. UI Conditional Rendering

```dart
class PatientListScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Consumer<UserState>(
      builder: (context, userState, child) {
        return Scaffold(
          appBar: AppBar(
            title: Text('Patients'),
            actions: [
              // Show create button only if user has permission
              if (userState.canCreatePatients)
                IconButton(
                  icon: Icon(Icons.add),
                  onPressed: () => _createPatient(context),
                ),
            ],
          ),
          body: Column(
            children: [
              // Show different content based on permissions
              if (userState.canViewPatients)
                Expanded(
                  child: PatientList(),
                )
              else
                Expanded(
                  child: Center(
                    child: Text('You do not have permission to view patients'),
                  ),
                ),
              
              // Show admin panel button only for admins
              if (userState.isAdmin)
                ElevatedButton(
                  onPressed: () => _openAdminPanel(context),
                  child: Text('Admin Panel'),
                ),
            ],
          ),
        );
      },
    );
  }
}

class PostCard extends StatelessWidget {
  final Post post;
  
  @override
  Widget build(BuildContext context) {
    return Consumer<UserState>(
      builder: (context, userState, child) {
        return Card(
          child: Column(
            children: [
              Text(post.title),
              Text(post.content),
              
              // Show moderation buttons only for moderators/admins
              if (userState.hasAnyRole(['moderator', 'admin']))
                Row(
                  children: [
                    if (userState.canModeratePosts)
                      ElevatedButton(
                        onPressed: () => _moderatePost(post),
                        child: Text('Moderate'),
                      ),
                    if (userState.isAdmin)
                      ElevatedButton(
                        onPressed: () => _deletePost(post),
                        child: Text('Delete'),
                      ),
                  ],
                ),
            ],
          ),
        );
      },
    );
  }
}
```

### 5. Permission-Based Navigation

```dart
class AppRouter {
  static Route<dynamic> generateRoute(RouteSettings settings) {
    switch (settings.name) {
      case '/patients':
        return MaterialPageRoute(
          builder: (_) => Consumer<UserState>(
            builder: (context, userState, child) {
              if (userState.canViewPatients) {
                return PatientListScreen();
              } else {
                return UnauthorizedScreen();
              }
            },
          ),
        );
      
      case '/admin':
        return MaterialPageRoute(
          builder: (_) => Consumer<UserState>(
            builder: (context, userState, child) {
              if (userState.isAdmin) {
                return AdminPanelScreen();
              } else {
                return UnauthorizedScreen();
              }
            },
          ),
        );
      
      default:
        return MaterialPageRoute(
          builder: (_) => NotFoundScreen(),
        );
    }
  }
}
```

### 6. Login Implementation

```dart
class LoginScreen extends StatefulWidget {
  @override
  _LoginScreenState createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Login')),
      body: Form(
        key: _formKey,
        child: Column(
          children: [
            TextFormField(
              controller: _emailController,
              decoration: InputDecoration(labelText: 'Email'),
              validator: (value) => value?.isEmpty == true ? 'Required' : null,
            ),
            TextFormField(
              controller: _passwordController,
              decoration: InputDecoration(labelText: 'Password'),
              obscureText: true,
              validator: (value) => value?.isEmpty == true ? 'Required' : null,
            ),
            SizedBox(height: 20),
            ElevatedButton(
              onPressed: _isLoading ? null : _login,
              child: _isLoading ? CircularProgressIndicator() : Text('Login'),
            ),
          ],
        ),
      ),
    );
  }
  
  Future<void> _login() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => _isLoading = true);
    
    try {
      final authService = AuthService();
      final loginResponse = await authService.login(
        _emailController.text,
        _passwordController.text,
      );
      
      // Update user state with roles and permissions
      final userState = Provider.of<UserState>(context, listen: false);
      userState.updateUser(
        loginResponse.data,
        loginResponse.roles,
        loginResponse.permissions,
      );
      
      // Navigate to appropriate screen based on role
      if (userState.isAdmin) {
        Navigator.pushReplacementNamed(context, '/admin');
      } else if (userState.isDoctor) {
        Navigator.pushReplacementNamed(context, '/dashboard');
      } else {
        Navigator.pushReplacementNamed(context, '/home');
      }
      
    } catch (e) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Login failed: $e')),
      );
    } finally {
      setState(() => _isLoading = false);
    }
  }
}
```

---

## Best Practices

### 1. **Permission Checking Strategy**

```dart
// ✅ Good: Check permissions in UI
if (userState.canCreatePatients) {
  showCreateButton();
}

// ✅ Good: Use role-based checks for major features
if (userState.isAdmin) {
  showAdminPanel();
}

// ❌ Avoid: Hardcoding permission names in UI
if (userState.hasPermission('create-patients')) {
  showCreateButton();
}
```

### 2. **Error Handling**

```dart
class PermissionErrorHandler {
  static void handleUnauthorized(BuildContext context, String action) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text('You do not have permission to $action'),
        backgroundColor: Colors.red,
      ),
    );
  }
  
  static Widget buildUnauthorizedScreen(String message) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.lock, size: 64, color: Colors.grey),
          SizedBox(height: 16),
          Text(
            'Access Denied',
            style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
          ),
          SizedBox(height: 8),
          Text(
            message,
            style: TextStyle(fontSize: 16, color: Colors.grey),
          ),
        ],
      ),
    );
  }
}
```

### 3. **Caching and Performance**

```dart
class PermissionCache {
  static const String _permissionsKey = 'user_permissions';
  static const String _rolesKey = 'user_roles';
  
  static Future<void> cachePermissions(List<String> permissions) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setStringList(_permissionsKey, permissions);
  }
  
  static Future<List<String>> getCachedPermissions() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getStringList(_permissionsKey) ?? [];
  }
  
  static Future<void> clearCache() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_permissionsKey);
    await prefs.remove(_rolesKey);
  }
}
```

---

## Testing

### 1. **Unit Tests**

```dart
void main() {
  group('UserState Permission Tests', () {
    late UserState userState;
    
    setUp(() {
      userState = UserState();
      userState.updateUser(
        User(id: 1, name: 'Test', email: 'test@test.com', /* ... */),
        ['doctor'],
        ['view-patients', 'create-patients'],
      );
    });
    
    test('should return true for existing permission', () {
      expect(userState.hasPermission('view-patients'), true);
    });
    
    test('should return false for non-existing permission', () {
      expect(userState.hasPermission('delete-patients'), false);
    });
    
    test('should return true for existing role', () {
      expect(userState.hasRole('doctor'), true);
    });
    
    test('should return false for non-existing role', () {
      expect(userState.hasRole('admin'), false);
    });
  });
}
```

### 2. **Widget Tests**

```dart
void main() {
  group('Permission-based Widget Tests', () {
    testWidgets('should show create button when user has permission', (tester) async {
      final userState = UserState();
      userState.updateUser(/* ... */, ['doctor'], ['create-patients']);
      
      await tester.pumpWidget(
        ChangeNotifierProvider.value(
          value: userState,
          child: MaterialApp(
            home: PatientListScreen(),
          ),
        ),
      );
      
      expect(find.byIcon(Icons.add), findsOneWidget);
    });
    
    testWidgets('should hide create button when user lacks permission', (tester) async {
      final userState = UserState();
      userState.updateUser(/* ... */, ['viewer'], ['view-patients']);
      
      await tester.pumpWidget(
        ChangeNotifierProvider.value(
          value: userState,
          child: MaterialApp(
            home: PatientListScreen(),
          ),
        ),
      );
      
      expect(find.byIcon(Icons.add), findsNothing);
    });
  });
}
```

---

## Migration Guide

### From Old System to New Enhanced System

#### 1. **Update API Calls**

```dart
// ❌ Old way
final response = await dio.post('/api/login');
final user = response.data['data'];
// No roles/permissions in response

// ✅ New way
final response = await dio.post('/api/v2/login');
final loginResponse = LoginResponse.fromJson(response.data);
final user = loginResponse.data;
final roles = loginResponse.roles;
final permissions = loginResponse.permissions;
```

#### 2. **Update State Management**

```dart
// ❌ Old way
class UserState {
  User? _user;
  // No roles/permissions storage
  
  bool canCreatePatients() {
    // Hardcoded or API call needed
    return _user?.role == 'admin';
  }
}

// ✅ New way
class UserState {
  User? _user;
  List<String> _roles = [];
  List<String> _permissions = [];
  
  bool get canCreatePatients => hasPermission('create-patients');
  bool get isAdmin => hasRole('admin');
}
```

#### 3. **Update UI Components**

```dart
// ❌ Old way
if (user?.role == 'admin') {
  showAdminButton();
}

// ✅ New way
if (userState.isAdmin) {
  showAdminButton();
}
```

---

## Troubleshooting

### Common Issues

#### 1. **Permissions Not Loading**

```dart
// Check if login response includes permissions
print('Login response: ${loginResponse.toJson()}');

// Verify API endpoint is v2
final response = await dio.post('/api/v2/login'); // ✅ Correct
// final response = await dio.post('/api/login'); // ❌ Old endpoint
```

#### 2. **Permission Checks Failing**

```dart
// Debug permission checking
print('User roles: ${userState.roles}');
print('User permissions: ${userState.permissions}');
print('Checking permission: create-patients');
print('Has permission: ${userState.hasPermission('create-patients')}');
```

#### 3. **State Not Updating**

```dart
// Ensure you're using Consumer or listening to changes
Consumer<UserState>(
  builder: (context, userState, child) {
    return Text('Permissions: ${userState.permissions.length}');
  },
)
```

---

## Summary

The enhanced permission system provides:

✅ **Immediate Access**: Permissions available on login  
✅ **Efficient Caching**: Reduced API calls  
✅ **Better UX**: No loading states for permission checks  
✅ **Flexible Updates**: Can refresh permissions without re-login  
✅ **Type Safety**: Strong typing with Dart models  
✅ **Easy Testing**: Comprehensive test coverage  
✅ **Maintainable**: Clean separation of concerns  

This implementation gives you a robust, scalable permission system that works seamlessly with your Flutter frontend! 🚀