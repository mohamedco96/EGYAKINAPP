# EGYAKIN Scripts

This directory contains testing and verification scripts for the EGYAKIN project.

## 📁 Directory Structure

### 🧪 Test Scripts (`tests/`)
Scripts to test specific module functionality and refactoring implementations:

- `test_array_handling.php` - Tests array handling and data processing
- `test_comment_refactoring.php` - Tests Comment module refactoring
- `test_questions_refactoring.php` - Tests Questions module refactoring  
- `test_recommendation_module.php` - Tests Recommendation module functionality
- `test_rolepermission_module.php` - Tests Role Permission module functionality
- `test_settings_module.php` - Tests Settings module functionality

### ✅ Verification Scripts (`verification/`)
Scripts to verify feature implementation and system integrity:

- `verify_export_feature.php` - Verifies patient export functionality
- `verify_notification_refactoring.php` - Verifies notification system refactoring

## 🚀 Usage

### Running Test Scripts
```bash
# From project root directory
cd /Users/mohamedibrahim/Documents/Work/EGYAKIN/EGYAKINAPP

# Test specific modules
php scripts/tests/test_comment_refactoring.php
php scripts/tests/test_questions_refactoring.php
php scripts/tests/test_recommendation_module.php
php scripts/tests/test_rolepermission_module.php
php scripts/tests/test_settings_module.php

# Test utilities
php scripts/tests/test_array_handling.php
```

### Running Verification Scripts
```bash
# Verify specific features
php scripts/verification/verify_export_feature.php
php scripts/verification/verify_notification_refactoring.php
```

## 📋 Script Guidelines

### Test Scripts
- **Purpose**: Validate that refactored modules work correctly
- **Pattern**: `test_[module_name].php`
- **Requirements**: Must bootstrap Laravel and test all major functionality
- **Output**: Clear success/failure indicators with detailed feedback

### Verification Scripts  
- **Purpose**: Verify that features are implemented and working as expected
- **Pattern**: `verify_[feature_name].php`
- **Requirements**: Must test end-to-end functionality
- **Output**: Comprehensive verification report

### Adding New Scripts
1. Follow the established naming convention
2. Include proper Laravel bootstrap if needed
3. Provide clear success/failure output
4. Document any prerequisites or setup requirements
5. Update this README with the new script

## 🔧 Development Notes

### Bootstrap Requirements
Most scripts require Laravel bootstrap to access:
- Eloquent models
- Service containers
- Database connections
- Configuration settings

### Error Handling
All scripts should include:
- Try-catch blocks for error handling
- Clear error messages with file/line information
- Graceful failure with appropriate exit codes

---

**Last Updated:** June 28, 2025  
**Total Test Scripts:** 6  
**Total Verification Scripts:** 2
