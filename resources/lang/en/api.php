<?php

return [
    // Authentication Messages
    'login_success' => 'Login successful',
    'login_failed' => 'Invalid credentials',
    'logout_success' => 'Logout successful',
    'registration_success' => 'Registration successful',
    'registration_failed' => 'Registration failed',
    'email_verification_sent' => 'Email verification sent',
    'email_verified' => 'Email verified successfully',
    'password_reset_sent' => 'Password reset link sent',
    'password_reset_success' => 'Password reset successful',

    // User Management
    'user_created' => 'User created successfully',
    'user_updated' => 'User updated successfully',
    'user_deleted' => 'User deleted successfully',
    'user_not_found' => 'User not found',
    'profile_updated' => 'Profile updated successfully',

    // Patient Management
    'patient_created' => 'Patient created successfully',
    'patient_updated' => 'Patient updated successfully',
    'patient_deleted' => 'Patient deleted successfully',
    'patient_not_found' => 'Patient not found',
    'patient_status_updated' => 'Patient status updated successfully',

    // Consultation & Assessment
    'consultation_created' => 'Consultation created successfully',
    'consultation_updated' => 'Consultation updated successfully',
    'assessment_completed' => 'Assessment completed successfully',
    'outcome_recorded' => 'Outcome recorded successfully',
    'section_completed' => 'Section completed successfully',

    // Notifications & Communications
    'notification_sent' => 'Notification sent successfully',
    'email_sent' => 'Email sent successfully',
    'reminder_sent' => 'Reminder sent successfully',
    'contact_request_sent' => 'Contact request sent successfully',

    // Scoring & Achievements
    'points_awarded' => 'Points awarded successfully',
    'milestone_reached' => 'Congratulations! You have reached :points points',
    'score_updated' => 'Score updated successfully',

    // Data & Reports
    'report_generated' => 'Report generated successfully',
    'data_exported' => 'Data exported successfully',
    'analytics_updated' => 'Analytics updated successfully',

    // General Messages
    'success' => 'Operation completed successfully',
    'error' => 'An error occurred',
    'validation_failed' => 'Validation failed',
    'unauthorized' => 'Unauthorized access',
    'forbidden' => 'Access forbidden',
    'not_found' => 'Resource not found',
    'server_error' => 'Internal server error',
    'maintenance_mode' => 'System is under maintenance',

    // File Operations
    'file_uploaded' => 'File uploaded successfully',
    'file_deleted' => 'File deleted successfully',
    'invalid_file_type' => 'Invalid file type',
    'file_too_large' => 'File size too large',

    // Permissions & Roles
    'permission_granted' => 'Permission granted',
    'permission_denied' => 'Permission denied',
    'role_assigned' => 'Role assigned successfully',
    'role_removed' => 'Role removed successfully',

    // OTP & Verification
    'otp_sent' => 'OTP sent successfully',
    'otp_verified' => 'OTP verified successfully',
    'otp_expired' => 'OTP has expired',
    'otp_invalid' => 'Invalid OTP',
    'verification_required' => 'Email verification required',

    // API Specific
    'invalid_request' => 'Invalid request format',
    'missing_parameters' => 'Missing required parameters',
    'rate_limit_exceeded' => 'Rate limit exceeded',
    'api_version_deprecated' => 'API version deprecated',

    // Auth & User Management
    'user_created_successfully' => 'User Created Successfully',
    'user_logged_in_successfully' => 'User Logged In Successfully',
    'user_logged_out_successfully' => 'User Logged Out Successfully',
    'user_updated_successfully' => 'User Updated Successfully',
    'user_deleted_successfully' => 'User Deleted Successfully',
    'registration_failed' => 'Registration failed',
    'invalid_credentials' => 'Invalid credentials',
    'too_many_login_attempts' => 'Too many login attempts. Please try again later.',
    'current_password_incorrect' => 'Current password is incorrect',
    'password_changed_successfully' => 'Password changed successfully',
    'profile_image_uploaded_successfully' => 'Profile image uploaded successfully.',
    'syndicate_card_uploaded_successfully' => 'User syndicate card uploaded successfully.',
    'no_user_found' => 'No User was found',
    'failed_to_retrieve_patients' => 'Failed to retrieve patients',
    'failed_to_retrieve_score_history' => 'Failed to retrieve score history.',

    // Patient Management
    'patient_created_successfully' => 'Patient Created Successfully',
    'section_updated_successfully' => 'Section updated successfully.',
    'patient_deleted_successfully' => 'Patient and related data deleted successfully',
    'section_not_found' => 'Section not found',
    'patient_not_found' => 'Patient not found',
    'error_occurred' => 'Error: :message',

    // Contact Management
    'contact_created_successfully' => 'Contact Created Successfully',
    'contact_updated_successfully' => 'Contact Updated Successfully',
    'no_contact_found' => 'No Contact was found',

    // Achievement Management
    'achievement_creation_error' => 'An error occurred while creating the achievement',
    'achievement_retrieval_error' => 'An error occurred while retrieving the achievement',

    // Consultation Management
    'consultation_created_successfully' => 'Consultation Created Successfully',
    'consultation_not_found' => 'Consultation not found.',
    'cannot_reply_closed_consultation' => 'Cannot reply to a closed consultation.',
    'consultation_updated_successfully' => 'Consultation request updated successfully',
    'cannot_add_doctors_closed_consultation' => 'Cannot add doctors to a closed consultation.',
    'doctors_already_in_consultation' => 'All selected doctors are already part of this consultation.',
    'doctors_added_successfully' => 'Doctors added to consultation successfully.',
    'consultation_not_authorized' => 'Consultation not found or you are not authorized to modify it.',
    'failed_to_add_doctors' => 'Failed to add doctors to consultation.',
    'failed_to_update_consultation_status' => 'Failed to update consultation status.',
    'not_authorized_view_consultation' => 'You are not authorized to view this consultation.',
    'failed_to_retrieve_consultation_members' => 'Failed to retrieve consultation members.',
    'cannot_reply_closed_consultation' => 'Cannot reply to a closed consultation.',
    'not_authorized_reply_consultation' => 'You are not authorized to reply to this consultation.',
    'reply_added_successfully' => 'Reply added successfully.',
    'failed_to_add_reply' => 'Failed to add reply.',
    'not_authorized_remove_doctors' => 'You are not authorized to remove doctors from this consultation.',
    'cannot_remove_consultation_creator' => 'Cannot remove the consultation creator.',

    // Email & Notifications
    'user_not_authenticated' => 'User not authenticated',
    'verification_email_sent_successfully' => 'Verification email sent successfully',
    'failed_to_send_verification_email' => 'Failed to send verification email',
    'invalid_or_expired_verification_link' => 'Invalid or expired verification link',
    'email_verified_successfully' => 'Email verified successfully',
    'new_contact_request' => 'New Contact Request',
    'reminder_from_egyakin' => 'Reminder from EGYAKIN',

    // Mail Content
    'hello_doctor' => 'Hello Doctor :name',
    'patient_outcome_not_submitted' => 'The Patient ":patient" outcome has not yet been submitted, please update it right now.',
    'patient_added_since' => 'Your Patient was added since :date',
    'thank_you_using_application' => 'Thank you for using our application!',
    'sincerely' => 'Sincerely,',
    'egyakin_scientific_team' => 'EGYAKIN Scientific Team.',
    'urgent_action_required' => 'Urgent Action Required',
    'patient_outcome_pending_message' => 'The patient outcome has not yet been submitted. Please update it immediately to ensure proper patient care documentation.',
    'patient_information' => 'Patient Information',
    'patient_name' => 'Patient Name',
    'added_since' => 'Added Since',
    'status' => 'Status',
    'outcome_pending' => 'Outcome Pending',
    'quality_care_commitment' => 'As part of our commitment to quality patient care, we need to ensure all patient outcomes are properly documented and submitted. This helps maintain accurate medical records and improves patient care quality.',
    'thank_you_attention' => 'Thank you for your attention to this matter and for using EGYAKIN! 🚀',
    'best_regards' => 'Best regards,',
    'automated_reminder' => 'This is an automated reminder. Please ensure patient outcomes are submitted promptly to maintain quality care standards.',
    'unknown_patient' => 'Unknown Patient',

    // Contact Request Content
    'hello_doctor_mostafa' => 'Hello Doctor Mostafa',
    'contact_request_message' => 'Dr.:name who works at :workplace has raised a new contact request.',
    'contact_message' => '<< :message >>',
    'contact_reach_info' => 'He can be reached by Email: :email or Phone: :phone',

    // Test & Debug
    'test_email_subject' => 'EGYAKIN Mail Test - :timestamp',
    'test_email_body' => 'This is a test email from EGYAKIN application to verify mail configuration.',
    'weekly_summary_subject' => 'EGYAKIN Weekly Summary - :week_start - :week_end',
];
