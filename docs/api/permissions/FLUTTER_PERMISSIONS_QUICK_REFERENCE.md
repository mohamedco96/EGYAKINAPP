# Flutter Permissions Quick Reference

## API Endpoints Summary

| Endpoint | Method | Auth Required | Purpose |
|----------|--------|---------------|---------|
| `/api/login` | POST | No | Login and get token |
| `/api/user` | GET | Yes | Get current user info |
| `/api/checkPermission` | POST | Yes | Check user permissions (limited) |
| `/api/assignRoleToUser` | POST | Yes | Assign role/permission to current user |
| `/api/createRoleAndPermission` | POST | Yes | Create roles/permissions or assign them |

## Permission Check Patterns

### Pattern 1: Simple Role Check
```dart
final isAdmin = await permissionService.hasRole('admin');
if (isAdmin) {
  // Show admin content
}
```

### Pattern 2: Simple Permission Check
```dart
final canDelete = await permissionService.hasPermission('delete patient');
if (canDelete) {
  // Show delete button
}
```

### Pattern 3: Widget Conditional Rendering
```dart
FutureBuilder<bool>(
  future: permissionService.hasPermission('edit posts'),
  builder: (context, snapshot) {
    if (snapshot.data == true) {
      return EditButton();
    }
    return SizedBox.shrink();
  },
)
```

### Pattern 4: Provider-Based (Riverpod)
```dart
final canDeleteProvider = FutureProvider<bool>((ref) async {
  final service = ref.read(permissionServiceProvider);
  return await service.hasPermission('delete patient');
});

// In widget:
final canDelete = ref.watch(canDeleteProvider);
canDelete.when(
  data: (can) => can ? DeleteButton() : SizedBox.shrink(),
  loading: () => CircularProgressIndicator(),
  error: (_, __) => SizedBox.shrink(),
)
```

## Common Permissions in System

Based on the backend implementation:

| Permission Name | Description |
|-----------------|-------------|
| `delete patient` | Can delete patient records |
| `edit posts` | Can edit posts/articles |
| `view reports` | Can view system reports |
| `manage users` | Can manage user accounts |

## Common Roles

| Role Name | Description |
|-----------|-------------|
| `admin` | Full system access |
| `moderator` | Content moderation access |
| `doctor` | Doctor user access |

## Flutter Setup Checklist

- [ ] Install dependencies: `shared_preferences`, `http` (or `dio`)
- [ ] Create `models/user_permission.dart`
- [ ] Create `models/user.dart`
- [ ] Create `services/api_service.dart`
- [ ] Create `services/permission_service.dart`
- [ ] Create `services/auth_service.dart`
- [ ] Create `widgets/permission_widget.dart` (optional)
- [ ] Set up state management (Provider/Riverpod/Bloc)
- [ ] Implement login with permission caching
- [ ] Implement permission checks in UI
- [ ] Add route guards for protected screens
- [ ] Implement permission refresh logic
- [ ] Test offline permission caching

## Backend Enhancement Checklist

- [ ] Modify login to return roles and permissions
- [ ] Create endpoint to get all user permissions
- [ ] Create flexible permission check endpoint
- [ ] Add permission middleware to protected routes
- [ ] Document available roles and permissions
- [ ] Create permission seeder

## Quick Code Snippets

### Initialize Services
```dart
final apiService = ApiService();
final permissionService = PermissionService(apiService);
final authService = AuthService(apiService, permissionService);
```

### Login Flow
```dart
final user = await authService.login(email, password);
// Permissions are automatically fetched and cached
```

### Logout Flow
```dart
await authService.logout();
// Clears token and permissions
```

### Refresh Permissions
```dart
await permissionService.fetchUserPermissions();
```

### Clear Permissions Cache
```dart
await permissionService.clearPermissions();
```

### Check Multiple Permissions
```dart
final permissions = await permissionService.getCachedPermissions();
final canEdit = permissions?.hasPermission('edit posts') ?? false;
final canDelete = permissions?.hasPermission('delete posts') ?? false;
final isAdmin = permissions?.isAdmin ?? false;
```

## Error Handling Examples

### Handle No Permission
```dart
try {
  final canDelete = await permissionService.hasPermission('delete patient');
  if (!canDelete) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Permission Denied'),
        content: Text('You don\'t have permission for this action.'),
      ),
    );
    return;
  }
  // Perform action
} catch (e) {
  // Handle error
}
```

### Handle Network Error
```dart
try {
  await permissionService.fetchUserPermissions();
} catch (e) {
  // Fall back to cached permissions
  final cached = await permissionService.getCachedPermissions();
  if (cached != null) {
    // Use cached permissions
  } else {
    // Show error
  }
}
```

## Best Practices Summary

1. **Cache First**: Always check cached permissions first for better UX
2. **Refresh Periodically**: Refresh permissions every 15-30 minutes
3. **Graceful Degradation**: Always provide fallback UI
4. **Offline Support**: Cache permissions locally
5. **Security**: Never rely solely on client-side permission checks
6. **Clear on Logout**: Always clear permissions when user logs out
7. **Loading States**: Show loading indicators while checking permissions
8. **Error Handling**: Handle permission check failures gracefully

## Dependencies

```yaml
# pubspec.yaml
dependencies:
  flutter:
    sdk: flutter
  http: ^1.1.0  # or dio: ^5.3.0
  shared_preferences: ^2.2.0
  flutter_riverpod: ^2.4.0  # optional, for state management
```

## Testing Permissions

### Test Permission Check
```dart
test('should return true for valid permission', () async {
  final service = PermissionService(mockApiService);
  final result = await service.hasPermission('delete patient');
  expect(result, true);
});
```

### Test Permission Caching
```dart
test('should cache permissions', () async {
  final service = PermissionService(mockApiService);
  final permissions = UserPermissions(roles: ['admin'], permissions: []);
  await service.cachePermissions(permissions);
  
  final cached = await service.getCachedPermissions();
  expect(cached?.roles, contains('admin'));
});
```

## Troubleshooting

### Issue: Permissions not loading
**Solution**: Check if token is valid and API endpoint is working

### Issue: UI not updating after permission change
**Solution**: Use state management to reactively update UI when permissions change

### Issue: Permissions lost after app restart
**Solution**: Ensure permissions are cached in SharedPreferences

### Issue: Permission check returns false for admin
**Solution**: Verify backend returns roles correctly and caching is working

For detailed implementation, see [FLUTTER_ROLES_PERMISSIONS_GUIDE.md](FLUTTER_ROLES_PERMISSIONS_GUIDE.md)

