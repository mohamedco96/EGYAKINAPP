# 📚 EGYAKIN Documentation

Welcome to the EGYAKIN medical platform documentation. This documentation is organized into clear categories for easy navigation and maintenance.

---

## 🗂️ **Documentation Structure**

### 🔌 **API Documentation** (`docs/api/`)

Complete API documentation including versioning and permissions.

#### **API Versioning**
- [`API_VERSIONING_IMPLEMENTATION.md`](api/API_VERSIONING_IMPLEMENTATION.md) - Complete API versioning system implementation
- [`API_VERSIONING_QUICK_REFERENCE.md`](api/API_VERSIONING_QUICK_REFERENCE.md) - Quick API reference guide
- [`V2_README.md`](api/V2_README.md) - API Version 2 comprehensive guide
- [`API_V2_SETUP_COMPLETE.md`](api/API_V2_SETUP_COMPLETE.md) - API v2 setup documentation
- [`V2_QUICK_START.md`](api/V2_QUICK_START.md) - Quick start guide for API v2

#### **Filtered Patients API**
- [`FILTERED_PATIENTS_PAYLOADS.md`](api/FILTERED_PATIENTS_PAYLOADS.md) - Simple payload examples for each request
- [`FILTERED_PATIENTS_REFERENCE.md`](api/FILTERED_PATIENTS_REFERENCE.md) - Technical reference with all parameters

#### **Marked Patients API**
- [`MARKED_PATIENTS_API.md`](api/MARKED_PATIENTS_API.md) - Complete guide with payloads for marking/unmarking patients

#### **Roles & Permissions** (`docs/api/permissions/`)
Complete role-based access control (RBAC) documentation for both Laravel and Flutter.

- [`README.md`](api/permissions/README.md) - **START HERE** - Navigation guide
- [`PERMISSIONS_IMPLEMENTATION_SUMMARY.md`](api/permissions/PERMISSIONS_IMPLEMENTATION_SUMMARY.md) - Complete system overview
- [`FLUTTER_ROLES_PERMISSIONS_GUIDE.md`](api/permissions/FLUTTER_ROLES_PERMISSIONS_GUIDE.md) - Complete Flutter implementation
- [`FLUTTER_PERMISSIONS_QUICK_REFERENCE.md`](api/permissions/FLUTTER_PERMISSIONS_QUICK_REFERENCE.md) - Quick reference & snippets
- [`BACKEND_PERMISSION_ENHANCEMENTS.md`](api/permissions/BACKEND_PERMISSION_ENHANCEMENTS.md) - Backend enhancement guide
- [`PERMISSIONS_FLOW_DIAGRAM.md`](api/permissions/PERMISSIONS_FLOW_DIAGRAM.md) - Visual flow diagrams

---

### 📧 **Email System** (`docs/email/`)

Email configuration, templates, and reporting system.

#### **Brevo API** (`docs/email/brevo/`)
- [`BREVO_DEFAULT_SETUP.md`](email/brevo/BREVO_DEFAULT_SETUP.md) - Brevo API integration guide

#### **Email Setup** (`docs/email/setup/`)
- [`EMAIL_REPORTING_SETUP.md`](email/setup/EMAIL_REPORTING_SETUP.md) - Daily/weekly report configuration

#### **Email Templates & Systems**
- [`MAIL_TEMPLATES_COMPLETE_GUIDE.md`](email/MAIL_TEMPLATES_COMPLETE_GUIDE.md) - All mail templates (10 templates)
- [`PATIENT_OUTCOME_REMINDER_SYSTEM.md`](email/PATIENT_OUTCOME_REMINDER_SYSTEM.md) - Automated patient reminders

---

### 🚀 **Deployment** (`docs/deployment/`)

Production deployment and server configuration.

- [`GODADDY_CRON_SETUP.md`](deployment/GODADDY_CRON_SETUP.md) - GoDaddy cron job configuration
- [`JOB_MONITORING_GUIDE.md`](deployment/JOB_MONITORING_GUIDE.md) - Queue job monitoring guide

---

### 🔧 **Setup & Configuration** (`docs/setup/`)

System setup, configuration, and initial installation.

- [`AUDIT_SYSTEM_SETUP.md`](setup/AUDIT_SYSTEM_SETUP.md) - Audit logging system setup
- [`FILAMENT_NAVIGATION_ORGANIZATION.md`](setup/FILAMENT_NAVIGATION_ORGANIZATION.md) - Admin panel navigation
- [`FILE_CLEANUP_DOCUMENTATION.md`](setup/FILE_CLEANUP_DOCUMENTATION.md) - File cleanup system
- [`PERMISSION_CATEGORY_SETUP.md`](setup/PERMISSION_CATEGORY_SETUP.md) - Permission categories setup

---

### 🆕 **Features** (`docs/features/`)

Feature implementations and enhancements.

#### **Access Control**
- [`blocked-user-home-access.md`](features/blocked-user-home-access.md) - Blocked user home access feature
- [`consultations-access-control.md`](features/consultations-access-control.md) - Consultations access control
- [`recommendations-access-control.md`](features/recommendations-access-control.md) - Recommendations access control

#### **Localization & Notifications**
- [`USER_LANGUAGE_PREFERENCE_SYSTEM.md`](features/USER_LANGUAGE_PREFERENCE_SYSTEM.md) - User language preferences
- [`DATABASE_NOTIFICATION_LOCALIZATION_SYSTEM.md`](features/DATABASE_NOTIFICATION_LOCALIZATION_SYSTEM.md) - Notification localization

#### **Patient Management**
- [`FILTERED_PATIENTS_EXPORT_API.md`](features/FILTERED_PATIENTS_EXPORT_API.md) - Patient export functionality
- [`ENHANCEMENT_CACHED_FILTERS.md`](features/ENHANCEMENT_CACHED_FILTERS.md) - Cached filters enhancement

#### **Resources**
- [`dose-resource-enhancements.md`](features/dose-resource-enhancements.md) - Dose resource improvements
- [`QUESTIONS_API_TEST_EXAMPLES.md`](features/QUESTIONS_API_TEST_EXAMPLES.md) - Questions API testing examples

#### **Status**
- [`IMPLEMENTATION_COMPLETE.md`](features/IMPLEMENTATION_COMPLETE.md) - Feature implementation status

---

### ⚡ **Performance** (`docs/performance/`)

Performance optimization documentation.

- [`patient-endpoint-optimization.md`](performance/patient-endpoint-optimization.md) - Patient endpoint optimization

---

### 🧪 **Testing** (`docs/testing/`)

Testing guides and checklists.

- [`TESTING_CHECKLIST.md`](testing/TESTING_CHECKLIST.md) - Complete testing checklist
- [`USER_ENDPOINTS_TESTING_GUIDE.md`](testing/USER_ENDPOINTS_TESTING_GUIDE.md) - User endpoint testing guide

---

### 🐛 **Bug Fixes** (`docs/bug-fixes/`)

Documented bug fixes.

- [`BUG_FIX_EXPORT_TRIM_ERROR.md`](bug-fixes/BUG_FIX_EXPORT_TRIM_ERROR.md) - Export trim error resolution

---

### 🔒 **Security** (`docs/security/`)

Security-related documentation.

- [`SECURITY_FIXES_REQUIRED.md`](security/SECURITY_FIXES_REQUIRED.md) - Required security fixes

---

### 📦 **Archive** (`docs/archive/`)

Historical documentation and outdated files kept for reference.

- Old implementation notes
- Completed refactoring documentation
- Historical bug fixes
- Superseded feature documentation

---

## 🎯 **Quick Start Guides**

### **For New Developers**
1. Read [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md) - Understand the project
2. Review [`docs/setup/`](setup/) - Configure your environment
3. Check [`docs/api/`](api/) - Understand API structure
4. Review [`docs/features/`](features/) - Learn implemented features

### **For Flutter Developers**
1. **Permissions**: [`docs/api/permissions/`](api/permissions/) - Complete RBAC guide
2. **API**: [`docs/api/V2_README.md`](api/V2_README.md) - API v2 documentation
3. **Features**: [`docs/features/`](features/) - Available features

### **For Backend Developers**
1. **Setup**: [`docs/setup/`](setup/) - System configuration
2. **API**: [`docs/api/`](api/) - API implementation
3. **Email**: [`docs/email/`](email/) - Email system
4. **Testing**: [`docs/testing/`](testing/) - Testing guides

### **For DevOps/Deployment**
1. **Deployment**: [`docs/deployment/`](deployment/) - Server setup
2. **Setup**: [`docs/setup/`](setup/) - Configuration
3. **Performance**: [`docs/performance/`](performance/) - Optimization

---

## 📋 **Project Overview**

- [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md) - Complete project overview and architecture

---

## 🔍 **Finding Documentation**

### By Topic

**Authentication & Permissions**
- [`docs/api/permissions/`](api/permissions/) - Complete RBAC documentation
- [`docs/setup/PERMISSION_CATEGORY_SETUP.md`](setup/PERMISSION_CATEGORY_SETUP.md) - Permission categories

**Email System**
- [`docs/email/MAIL_TEMPLATES_COMPLETE_GUIDE.md`](email/MAIL_TEMPLATES_COMPLETE_GUIDE.md) - All templates
- [`docs/email/setup/`](email/setup/) - Email configuration

**API Development**
- [`docs/api/API_VERSIONING_IMPLEMENTATION.md`](api/API_VERSIONING_IMPLEMENTATION.md) - API versioning
- [`docs/api/V2_README.md`](api/V2_README.md) - API v2 guide

**Patient Management**
- [`docs/api/FILTERED_PATIENTS_PAYLOADS.md`](api/FILTERED_PATIENTS_PAYLOADS.md) - **Payload examples** (START HERE)
- [`docs/api/FILTERED_PATIENTS_REFERENCE.md`](api/FILTERED_PATIENTS_REFERENCE.md) - Technical reference
- [`docs/features/FILTERED_PATIENTS_EXPORT_API.md`](features/FILTERED_PATIENTS_EXPORT_API.md) - Export functionality
- [`docs/performance/patient-endpoint-optimization.md`](performance/patient-endpoint-optimization.md) - Performance

**Deployment & Monitoring**
- [`docs/deployment/GODADDY_CRON_SETUP.md`](deployment/GODADDY_CRON_SETUP.md) - Cron jobs
- [`docs/deployment/JOB_MONITORING_GUIDE.md`](deployment/JOB_MONITORING_GUIDE.md) - Job monitoring

---

## 📊 **Documentation Statistics**

- **Total Active Documentation**: ~40 files
- **Main Categories**: 10
- **Archived Documents**: ~20+ historical files
- **Last Major Organization**: October 2024

---

## ✨ **Documentation Standards**

### File Naming
- Use descriptive names in UPPERCASE_WITH_UNDERSCORES.md
- Include category prefix when helpful (e.g., `FEATURE_`, `BUG_FIX_`)
- Use clear, searchable titles

### Structure
- Start with overview/purpose
- Include table of contents for long docs
- Use clear sections with headers
- Include code examples where applicable
- Add troubleshooting sections

### Maintenance
- Archive outdated documentation (don't delete)
- Update README when adding new docs
- Keep implementation guides up to date
- Remove duplicate content

---

## 🔄 **Recently Updated**

- ✅ **Filtered Patients API Documentation** (October 2025) - Simplified to 2 focused docs
- ✅ API Permissions documentation (October 2024)
- ✅ API v2 documentation consolidated
- ✅ Historical files archived
- ✅ Removed duplicate and outdated files
- ✅ Reorganized setup documentation

---

## 📞 **Support**

**Can't find what you need?**
1. Check the [`archive/`](archive/) folder for historical documentation
2. Search within files using your IDE's search functionality
3. Review the [`PROJECT_OVERVIEW.md`](PROJECT_OVERVIEW.md) for high-level understanding
4. Contact the development team

---

## 📝 **Contributing to Documentation**

When adding new documentation:
1. Place it in the appropriate category folder
2. Follow the naming conventions
3. Update this README with a link
4. Include clear examples and use cases
5. Add a table of contents for long documents

---

**📍 Location**: `/docs/`  
**👥 Maintained by**: EGYAKIN Development Team  
**📅 Last Updated**: October 2024  
**🔄 Version**: 2.0 - Comprehensive Organization
