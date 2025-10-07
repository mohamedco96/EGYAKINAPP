# Roles & Permissions Documentation

Complete documentation for implementing role-based access control (RBAC) in the EGYAKIN platform, covering both Laravel backend and Flutter frontend.

## 📚 Documentation Index

### 🌟 Start Here
**[PERMISSIONS_IMPLEMENTATION_SUMMARY.md](PERMISSIONS_IMPLEMENTATION_SUMMARY.md)**
- Complete overview of the system
- What's working and what needs enhancement
- Implementation roadmap and timelines
- Quick start guides for both backend and Flutter teams
- Estimated effort: 6-9 hours total

---

### 📱 Flutter Implementation
**[FLUTTER_ROLES_PERMISSIONS_GUIDE.md](FLUTTER_ROLES_PERMISSIONS_GUIDE.md)**
- Complete Flutter implementation guide (50+ pages)
- Data models and service layer architecture
- Permission checking strategies (online, cached, hybrid)
- UI conditional rendering with Flutter widgets
- Provider/Riverpod state management examples
- Route guards and navigation protection
- Complete working code examples
- Best practices and error handling

**Target Audience:** Flutter developers  
**Estimated Reading Time:** 45-60 minutes  
**Implementation Time:** 4-6 hours

---

### ⚡ Quick Reference
**[FLUTTER_PERMISSIONS_QUICK_REFERENCE.md](FLUTTER_PERMISSIONS_QUICK_REFERENCE.md)**
- Quick lookup reference cards
- Copy-paste code snippets
- Common permission check patterns
- Available permissions and roles list
- Setup checklists
- Troubleshooting guide
- Testing examples

**Target Audience:** All developers  
**Estimated Reading Time:** 10-15 minutes  
**Use Case:** Quick lookups during development

---

### 🔧 Backend Enhancements
**[BACKEND_PERMISSION_ENHANCEMENTS.md](BACKEND_PERMISSION_ENHANCEMENTS.md)**
- Current limitations analysis
- Copy-paste Laravel code enhancements
- Login response enhancement (include permissions)
- New API endpoints:
  - `GET /userPermissions`
  - `POST /checkMultiplePermissions`
- Permission seeder with all roles and permissions
- Middleware implementation for route protection
- User model helper methods
- Testing examples

**Target Audience:** Backend/Laravel developers  
**Estimated Reading Time:** 30-40 minutes  
**Implementation Time:** 4-5 hours

---

### 📊 Flow Diagrams
**[PERMISSIONS_FLOW_DIAGRAM.md](PERMISSIONS_FLOW_DIAGRAM.md)**
- Visual system architecture
- User authentication flow diagrams
- Permission check flow (online vs cached)
- UI conditional rendering flow
- Route guard flow
- Permission lifecycle
- State management flow (Riverpod)
- Complete user journey map
- Security layers (defense in depth)
- Performance optimization visualization

**Target Audience:** All developers, architects  
**Estimated Reading Time:** 20-30 minutes  
**Use Case:** Understanding system architecture and data flow

---

## 🚀 Quick Navigation

### By Role

**👨‍💻 If you're a Backend Developer:**
1. Read: [Summary](PERMISSIONS_IMPLEMENTATION_SUMMARY.md)
2. Implement: [Backend Enhancements](BACKEND_PERMISSION_ENHANCEMENTS.md)
3. Reference: [Flow Diagrams](PERMISSIONS_FLOW_DIAGRAM.md)

**📱 If you're a Flutter Developer:**
1. Read: [Summary](PERMISSIONS_IMPLEMENTATION_SUMMARY.md)
2. Implement: [Flutter Guide](FLUTTER_ROLES_PERMISSIONS_GUIDE.md)
3. Use: [Quick Reference](FLUTTER_PERMISSIONS_QUICK_REFERENCE.md)
4. Visualize: [Flow Diagrams](PERMISSIONS_FLOW_DIAGRAM.md)

**👔 If you're a Project Manager:**
1. Read: [Summary](PERMISSIONS_IMPLEMENTATION_SUMMARY.md) (focus on timelines and estimates)
2. Review: [Flow Diagrams](PERMISSIONS_FLOW_DIAGRAM.md) (understand architecture)

**🏗️ If you're a System Architect:**
1. Read: [Summary](PERMISSIONS_IMPLEMENTATION_SUMMARY.md)
2. Study: [Flow Diagrams](PERMISSIONS_FLOW_DIAGRAM.md)
3. Review: [Backend Enhancements](BACKEND_PERMISSION_ENHANCEMENTS.md)
4. Review: [Flutter Guide](FLUTTER_ROLES_PERMISSIONS_GUIDE.md)

---

## 🎯 Implementation Roadmap

### Phase 1: Backend (4-5 hours)
- [ ] Enhance login response to include permissions
- [ ] Add `getUserPermissions` endpoint
- [ ] Add `checkMultiplePermissions` endpoint  
- [ ] Create and run permissions seeder
- [ ] (Optional) Add permission middleware
- [ ] Test all endpoints

**Guide:** [BACKEND_PERMISSION_ENHANCEMENTS.md](BACKEND_PERMISSION_ENHANCEMENTS.md)

### Phase 2: Flutter (4-6 hours)
- [ ] Create data models
- [ ] Implement service layer
- [ ] Set up caching with SharedPreferences
- [ ] Create permission widgets
- [ ] Integrate with state management
- [ ] Add route guards
- [ ] Test with different user roles

**Guide:** [FLUTTER_ROLES_PERMISSIONS_GUIDE.md](FLUTTER_ROLES_PERMISSIONS_GUIDE.md)

### Phase 3: Testing & Deployment (1-2 hours)
- [ ] End-to-end testing
- [ ] Performance testing
- [ ] Security audit
- [ ] Documentation updates
- [ ] Deploy backend
- [ ] Deploy Flutter app

---

## 📋 Available Permissions (After Seeding)

### Patient Management
- `view patients` - View patient list
- `create patients` - Create new patients
- `edit patients` - Edit patient details
- `delete patient` - Delete patients

### Content Management
- `view posts` - View posts
- `create posts` - Create posts
- `edit posts` - Edit posts
- `delete posts` - Delete posts
- `moderate posts` - Moderate user content

### User Management
- `view users` - View user list
- `create users` - Create users
- `edit users` - Edit users
- `delete users` - Delete users

### Reports & Analytics
- `view reports` - View reports
- `export data` - Export data

### Role Management
- `view roles` - View roles
- `manage roles` - Manage roles
- `assign roles` - Assign roles

### System Settings
- `view settings` - View settings
- `edit settings` - Edit settings

---

## 🛡️ Available Roles (After Seeding)

### Admin
- **Permissions:** ALL
- **Access:** Full system access
- **Use Case:** System administrators

### Moderator
- **Permissions:** View users/patients/posts, Create/edit patients/posts, Moderate content, View reports
- **Access:** Content moderation and patient management
- **Use Case:** Content moderators and senior doctors

### Doctor
- **Permissions:** View/create/edit patients and posts
- **Access:** Basic patient and content management
- **Use Case:** Regular doctors

---

## 🔑 Key Concepts

### Backend (Laravel + Spatie)
- **Roles**: Groups of permissions (e.g., "admin", "doctor")
- **Permissions**: Specific actions (e.g., "delete patient")
- **Assignment**: Users can have roles AND direct permissions
- **Inheritance**: Permissions from roles are automatically available
- **Guard**: Uses 'web' guard with Sanctum authentication

### Flutter (Client-Side)
- **Caching**: Store permissions locally for offline access
- **Checking**: Verify permissions before showing UI elements
- **Refresh**: Periodically update permissions from server
- **Fallback**: Use cached permissions when offline
- **Security**: Client checks are for UX only, server always validates

---

## 🔒 Security Notes

⚠️ **Critical Security Principles:**

1. **Never trust the client**: Always validate permissions on the server
2. **Defense in depth**: Check permissions at multiple layers
3. **Server-side enforcement**: Use middleware on protected routes
4. **Audit logging**: Log all permission checks
5. **Regular updates**: Refresh permissions periodically
6. **Clear on logout**: Always clear cached permissions

---

## 📊 System Architecture

```
┌─────────────────────────────────────────────────┐
│            EGYAKIN Platform                     │
├─────────────────────────────────────────────────┤
│                                                 │
│  ┌──────────┐              ┌──────────┐        │
│  │ Flutter  │◄───────────►│ Laravel  │        │
│  │   App    │   REST API  │ Backend  │        │
│  └─────┬────┘             └─────┬────┘        │
│        │                        │              │
│  ┌─────▼──────┐          ┌──────▼─────┐       │
│  │Local Cache │          │  Spatie    │       │
│  │(SharedPref)│          │ Permission │       │
│  └────────────┘          └────────────┘       │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## 📞 Support

**Questions or Issues?**
- Review the [Summary](PERMISSIONS_IMPLEMENTATION_SUMMARY.md) first
- Check [Quick Reference](FLUTTER_PERMISSIONS_QUICK_REFERENCE.md) for common patterns
- Refer to [Flow Diagrams](PERMISSIONS_FLOW_DIAGRAM.md) for architecture questions
- Consult implementation guides for detailed code

**Found a bug or need clarification?**
- Check the troubleshooting sections in each guide
- Review error handling examples
- Contact the development team

---

## 📝 Version History

- **v1.0** - Initial documentation (October 2024)
  - Complete system documentation
  - Implementation guides
  - Code examples
  - Flow diagrams

---

**📍 Location:** `/docs/api/permissions/`  
**👥 Maintained by:** EGYAKIN Development Team  
**📅 Last Updated:** October 2024

