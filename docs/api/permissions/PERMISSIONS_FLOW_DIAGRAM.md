# Role & Permission Flow Diagrams

## System Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                     EGYAKIN Platform                        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────┐              ┌──────────────┐            │
│  │   Flutter    │◄────────────►│   Laravel    │            │
│  │     App      │   REST API   │   Backend    │            │
│  └──────────────┘              └──────────────┘            │
│        │                              │                     │
│        │                              │                     │
│   ┌────▼────┐                   ┌────▼────┐                │
│   │ Local   │                   │  Spatie │                │
│   │ Cache   │                   │Permission│               │
│   └─────────┘                   └──────────┘               │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## User Login Flow (Current vs Enhanced)

### Current Flow (Without Enhancements)

```
┌─────────┐         ┌─────────┐         ┌──────────┐
│ Flutter │         │ Laravel │         │ Database │
└────┬────┘         └────┬────┘         └────┬─────┘
     │                   │                    │
     │ POST /login       │                    │
     ├──────────────────►│                    │
     │  email, password  │                    │
     │                   │ Authenticate       │
     │                   ├───────────────────►│
     │                   │                    │
     │                   │◄───────────────────┤
     │                   │  User data         │
     │◄──────────────────┤                    │
     │  token + user     │                    │
     │                   │                    │
     │ POST /checkPermission                  │
     ├──────────────────►│                    │
     │                   │ Check permissions  │
     │                   ├───────────────────►│
     │                   │                    │
     │◄──────────────────┤                    │
     │  limited result   │                    │
     │                   │                    │
```

**Issues:**
- 2 API calls needed
- Limited permission data
- Slower login experience
- Extra network overhead

---

### Enhanced Flow (Recommended)

```
┌─────────┐         ┌─────────┐         ┌──────────┐
│ Flutter │         │ Laravel │         │ Database │
└────┬────┘         └────┬────┘         └────┬─────┘
     │                   │                    │
     │ POST /login       │                    │
     ├──────────────────►│                    │
     │  email, password  │                    │
     │                   │ Authenticate       │
     │                   ├───────────────────►│
     │                   │                    │
     │                   │ Load roles &       │
     │                   │ permissions        │
     │                   ├───────────────────►│
     │                   │                    │
     │◄──────────────────┤                    │
     │  token + user +   │                    │
     │  roles +          │                    │
     │  permissions      │                    │
     │                   │                    │
     ├─Cache locally     │                    │
     │                   │                    │
```

**Benefits:**
- ✅ 1 API call
- ✅ Complete permission data
- ✅ Faster login
- ✅ Immediate offline access

---

## Permission Check Flow

### Online Check (Real-time)

```
┌─────────┐         ┌─────────┐         ┌──────────┐
│ Flutter │         │ Laravel │         │ Database │
└────┬────┘         └────┬────┘         └────┬─────┘
     │                   │                    │
     │ Check "delete     │                    │
     │ patient"          │                    │
     ├─┐                 │                    │
     │ │ Check cache     │                    │
     │ │ (expired)       │                    │
     │◄┘                 │                    │
     │                   │                    │
     │ POST /checkMultiplePermissions         │
     ├──────────────────►│                    │
     │ permissions:      │                    │
     │ ["delete patient"]│                    │
     │                   │ Check DB           │
     │                   ├───────────────────►│
     │                   │                    │
     │◄──────────────────┤                    │
     │ {"delete patient": true}               │
     │                   │                    │
     ├─Update cache      │                    │
     │                   │                    │
     ├─Show Delete Btn   │                    │
     │                   │                    │
```

---

### Offline Check (Cached)

```
┌─────────┐         ┌──────────────┐
│ Flutter │         │ Local Cache  │
└────┬────┘         └──────┬───────┘
     │                     │
     │ Check "delete       │
     │ patient"            │
     ├────────────────────►│
     │                     │
     │◄────────────────────┤
     │ true (from cache)   │
     │                     │
     ├─Show Delete Btn     │
     │                     │
```

**Benefits:**
- ⚡ Instant response
- 📱 Works offline
- 💰 No API cost
- 🎯 Better UX

---

## Hybrid Approach (Recommended)

```
┌─────────┐         ┌──────────────┐         ┌─────────┐
│ Flutter │         │ Local Cache  │         │ Laravel │
└────┬────┘         └──────┬───────┘         └────┬────┘
     │                     │                       │
     │ Check "delete       │                       │
     │ patient"            │                       │
     ├────────────────────►│                       │
     │                     │                       │
     │ Cache hit?          │                       │
     ├─┐                   │                       │
     │ │ Yes, cache valid  │                       │
     │◄┘                   │                       │
     │                     │                       │
     │◄────────────────────┤                       │
     │ true (from cache)   │                       │
     │                     │                       │
     ├─Show Delete Btn     │                       │
     │ (instant)           │                       │
     │                     │                       │
     │ Refresh in background                       │
     ├────────────────────────────────────────────►│
     │ (if cache old)      │                       │
     │                     │                       │
     │◄────────────────────────────────────────────┤
     │ Updated permissions │                       │
     │                     │                       │
     ├────────────────────►│                       │
     │ Update cache        │                       │
     │                     │                       │
```

**Best of both worlds:**
- ⚡ Instant UI response
- 🔄 Always up-to-date
- 📱 Offline capable
- 🎯 Optimal UX

---

## UI Conditional Rendering Flow

```
┌──────────────────────────────────────────────────┐
│              Patient Detail Screen               │
├──────────────────────────────────────────────────┤
│                                                  │
│  User opens screen                               │
│  ↓                                               │
│  Check permission: "delete patient"              │
│  ↓                                               │
│  ┌────────────┐    ┌─────────────┐             │
│  │ Has Perm?  │───►│   Yes       │             │
│  └────────────┘    └──────┬──────┘             │
│        │ No               │                     │
│        │                  ↓                     │
│        │           ┌──────────────┐            │
│        │           │ Show Delete  │            │
│        │           │   Button     │            │
│        │           └──────────────┘            │
│        │                                        │
│        ↓                                        │
│  ┌──────────────┐                              │
│  │ Hide Delete  │                              │
│  │   Button     │                              │
│  └──────────────┘                              │
│                                                 │
└──────────────────────────────────────────────────┘
```

---

## Route Guard Flow

```
┌─────────────────────────────────────────────────┐
│        User navigates to /admin route           │
└──────────────────┬──────────────────────────────┘
                   │
                   ↓
        ┌──────────────────────┐
        │  Route Guard Check   │
        └──────────┬───────────┘
                   │
                   ↓
        ┌──────────────────────┐
        │ Has 'admin' role?    │
        └──────────┬───────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
       Yes                   No
        │                     │
        ↓                     ↓
┌───────────────┐    ┌─────────────────┐
│  Allow access │    │  Redirect to    │
│  Show admin   │    │  /unauthorized  │
│  panel        │    │  page           │
└───────────────┘    └─────────────────┘
```

---

## Permission Lifecycle

```
┌──────────────────────────────────────────────────┐
│              Permission Lifecycle                │
└──────────────────────────────────────────────────┘

1. LOGIN
   ┌────────────────┐
   │ User logs in   │
   └────────┬───────┘
            │
            ↓
   ┌────────────────────┐
   │ Fetch permissions  │
   │ from backend       │
   └────────┬───────────┘
            │
            ↓
   ┌────────────────────┐
   │ Cache locally      │
   │ (SharedPreferences)│
   └────────┬───────────┘
            │
            ↓

2. USAGE
   ┌────────────────────┐
   │ Read from cache    │
   │ (instant access)   │
   └────────┬───────────┘
            │
            ↓
   ┌────────────────────┐
   │ Refresh every      │
   │ 15-30 minutes      │
   └────────┬───────────┘
            │
            ↓

3. UPDATE
   ┌────────────────────┐
   │ Permission changed │
   │ on backend         │
   └────────┬───────────┘
            │
            ↓
   ┌────────────────────┐
   │ Next refresh cycle │
   │ picks up changes   │
   └────────┬───────────┘
            │
            ↓

4. LOGOUT
   ┌────────────────────┐
   │ User logs out      │
   └────────┬───────────┘
            │
            ↓
   ┌────────────────────┐
   │ Clear cache        │
   │ Remove tokens      │
   └────────────────────┘
```

---

## State Management Flow (Riverpod)

```
┌─────────────────────────────────────────────────┐
│               Widget Tree                       │
└──────────────────┬──────────────────────────────┘
                   │
                   │ ref.watch(isAdminProvider)
                   │
                   ↓
┌─────────────────────────────────────────────────┐
│           isAdminProvider                       │
│  (FutureProvider)                               │
└──────────────────┬──────────────────────────────┘
                   │
                   │ ref.read(permissionProvider)
                   │
                   ↓
┌─────────────────────────────────────────────────┐
│         permissionProvider                      │
│  (Returns UserPermissions)                      │
└──────────────────┬──────────────────────────────┘
                   │
                   │ ref.read(permissionService)
                   │
                   ↓
┌─────────────────────────────────────────────────┐
│        PermissionService                        │
│  - getCachedPermissions()                       │
│  - fetchUserPermissions()                       │
│  - hasRole()                                    │
│  - hasPermission()                              │
└──────────────────┬──────────────────────────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
        ↓                     ↓
┌───────────────┐    ┌────────────────┐
│ Local Cache   │    │  API Service   │
│(SharedPrefs)  │    │  (Network)     │
└───────────────┘    └────────────────┘
```

---

## Error Handling Flow

```
┌─────────────────────────────────────────────────┐
│          Permission Check Request               │
└──────────────────┬──────────────────────────────┘
                   │
                   ↓
        ┌──────────────────────┐
        │  Try fetch from API  │
        └──────────┬───────────┘
                   │
        ┌──────────┴──────────┐
        │                     │
     Success                Error
        │                     │
        ↓                     ↓
┌───────────────┐    ┌─────────────────┐
│ Update cache  │    │  Check error    │
│ Return result │    │  type           │
└───────────────┘    └────────┬────────┘
                              │
                   ┌──────────┼──────────┐
                   │          │          │
              Network    401       Other
                Error   Unauth    Error
                   │          │          │
                   ↓          ↓          ↓
           ┌────────────┐ ┌──────────┐ ┌──────────┐
           │ Use cached │ │ Redirect │ │  Show    │
           │permissions │ │ to login │ │  error   │
           └────────────┘ └──────────┘ └──────────┘
```

---

## Complete User Journey

```
┌──────────────────────────────────────────────────────────┐
│                   User Journey                           │
└──────────────────────────────────────────────────────────┘

1. APP LAUNCH
   │
   ├─ Check if logged in (has token)
   │  │
   │  ├─ Yes → Load cached permissions → Show home
   │  └─ No → Show login screen
   │
   ↓

2. LOGIN
   │
   ├─ Enter credentials
   ├─ POST /api/login
   ├─ Receive: token + user + roles + permissions
   ├─ Cache permissions locally
   ├─ Navigate to home
   │
   ↓

3. HOME SCREEN
   │
   ├─ Read cached permissions (instant)
   ├─ Render UI based on roles:
   │  ├─ Admin → Show all features
   │  ├─ Moderator → Show limited features
   │  └─ Doctor → Show basic features
   │
   ↓

4. NAVIGATE TO PATIENT DETAIL
   │
   ├─ Check permission: "delete patient"
   ├─ Show/hide delete button accordingly
   ├─ User clicks delete (if has permission)
   │  │
   │  ├─ Frontend: Check permission again
   │  ├─ Backend: Middleware verifies permission
   │  └─ Action executed if authorized
   │
   ↓

5. PERMISSION REFRESH
   │
   ├─ Every 15-30 minutes:
   │  ├─ Fetch latest permissions in background
   │  ├─ Update cache
   │  └─ UI updates if permissions changed
   │
   ↓

6. LOGOUT
   │
   ├─ POST /api/logout
   ├─ Clear token
   ├─ Clear cached permissions
   └─ Navigate to login screen
```

---

## Security Flow (Defense in Depth)

```
┌──────────────────────────────────────────────────┐
│              Security Layers                     │
└──────────────────────────────────────────────────┘

Layer 1: FLUTTER UI
┌────────────────────────────────────────────┐
│ - Check cached permissions                 │
│ - Hide/show UI elements                    │
│ - Provide better UX                        │
└──────────────┬─────────────────────────────┘
               │
               ↓
Layer 2: API REQUEST
┌────────────────────────────────────────────┐
│ - Include auth token                       │
│ - Send request to protected endpoint      │
└──────────────┬─────────────────────────────┘
               │
               ↓
Layer 3: LARAVEL MIDDLEWARE
┌────────────────────────────────────────────┐
│ - Verify token (Sanctum)                   │
│ - Check permission (Spatie)                │
│ - Reject if unauthorized (403)             │
└──────────────┬─────────────────────────────┘
               │
               ↓
Layer 4: CONTROLLER
┌────────────────────────────────────────────┐
│ - Additional business logic checks         │
│ - Verify resource ownership                │
│ - Execute action                           │
└────────────────────────────────────────────┘

⚠️ NEVER rely only on frontend checks!
✅ Backend ALWAYS verifies permissions
```

---

## Data Structures

### UserPermissions Object (Flutter)

```dart
UserPermissions {
  roles: ["admin", "moderator"],
  permissions: [
    "delete patient",
    "edit posts",
    "view reports"
  ]
}
```

### Cached in SharedPreferences

```json
{
  "roles": ["admin"],
  "permissions": ["delete patient", "edit posts"],
  "cached_at": "2024-10-04T12:00:00Z"
}
```

### API Response Structure

```json
{
  "value": true,
  "roles": {
    "admin": true,
    "moderator": false
  },
  "permissions": {
    "delete patient": true,
    "edit posts": true,
    "view reports": false
  }
}
```

---

## Performance Optimization

```
┌─────────────────────────────────────────────────┐
│        Performance Optimization Flow            │
└─────────────────────────────────────────────────┘

1. LOGIN
   ├─ Fetch permissions ONCE
   ├─ Cache for 30 minutes
   └─ Cost: 1 API call
   
2. PERMISSION CHECKS (x100 checks)
   ├─ Read from cache
   ├─ Cost: 0 API calls
   └─ Response time: <1ms
   
3. BACKGROUND REFRESH
   ├─ Every 30 minutes
   ├─ Silent update
   └─ Cost: 1 API call per 30min
   
TOTAL: ~2 API calls per hour
vs. NO CACHING: 100+ API calls per hour

IMPROVEMENT: 98% reduction in API calls!
```

---

## For More Details

- **Complete Guide**: [FLUTTER_ROLES_PERMISSIONS_GUIDE.md](FLUTTER_ROLES_PERMISSIONS_GUIDE.md)
- **Quick Reference**: [FLUTTER_PERMISSIONS_QUICK_REFERENCE.md](FLUTTER_PERMISSIONS_QUICK_REFERENCE.md)
- **Backend Enhancements**: [BACKEND_PERMISSION_ENHANCEMENTS.md](BACKEND_PERMISSION_ENHANCEMENTS.md)
- **Summary**: [PERMISSIONS_IMPLEMENTATION_SUMMARY.md](PERMISSIONS_IMPLEMENTATION_SUMMARY.md)

