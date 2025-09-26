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
    'account_blocked' => 'Your account has been blocked. Please contact support.',
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

    // Post & Feed Management
    'no_feed_posts_found' => 'No feed posts found',
    'feed_posts_retrieved_successfully' => 'Feed posts retrieved successfully',
    'error_retrieving_feed_posts' => 'An error occurred while retrieving feed posts',
    'post_comments_retrieved_successfully' => 'Post comments retrieved successfully',
    'error_retrieving_post_comments' => 'An error occurred while retrieving post comments',
    'post_created_successfully' => 'Post created successfully',
    'error_creating_post' => 'Error creating post: :message',
    'post_updated_successfully' => 'Post updated successfully',
    'post_not_found' => 'Post not found',
    'error_updating_post' => 'An error occurred while updating the post: :message',
    'comment_added_successfully' => 'Comment added successfully',
    'error_adding_comment' => 'An error occurred while adding comment',
    'post_liked_successfully' => 'Post liked successfully',
    'post_unliked_successfully' => 'Post unliked successfully',
    'error_liking_post' => 'An error occurred while liking/unliking the post',
    'post_saved_successfully' => 'Post saved successfully',
    'post_unsaved_successfully' => 'Post unsaved successfully',
    'error_saving_post' => 'An error occurred while saving/unsaving the post',
    'post_deleted_successfully' => 'Post deleted successfully',
    'error_deleting_post' => 'An error occurred while deleting the post',
    'comment_liked_successfully' => 'Comment liked successfully',
    'comment_unliked_successfully' => 'Comment unliked successfully',
    'error_liking_comment' => 'An error occurred while liking/unliking the comment',

    // Group Management
    'header_picture_upload_failed' => 'Header picture upload failed.',
    'group_image_upload_failed' => 'Group image upload failed.',
    'group_created_successfully' => 'Group created successfully',
    'error_creating_group' => 'An error occurred while creating the group',
    'group_updated_successfully' => 'Group updated successfully',
    'group_not_found' => 'Group not found',
    'validation_failed' => 'Validation failed',
    'error_updating_group' => 'An error occurred while updating the group',
    'group_deleted_successfully' => 'Group deleted successfully',
    'invitations_processed' => 'Invitations processed',
    'error_processing_invitations' => 'An error occurred while processing invitations',
    'invalid_invitation' => 'Invalid invitation',
    'error_handling_invitation' => 'An error occurred while handling the invitation',
    'group_details_retrieved_successfully' => 'Group details retrieved successfully',
    'member_not_found_in_group' => 'Member not found in the group',
    'member_removed_successfully' => 'Member removed successfully',
    'members_search_results' => 'Members search results',
    'community_members_fetched_successfully' => 'Community members and pending invitations fetched successfully',
    'error_fetching_members_invitations' => 'An error occurred while fetching members and invitations',
    'group_details_posts_fetched_successfully' => 'Group details with paginated posts fetched successfully',
    'already_member_of_group' => 'You are already a member of this group',
    'not_member_of_group' => 'You are not a member of this group',
    'left_group_successfully' => 'Left group successfully',
    'user_groups_fetched_successfully' => 'User groups fetched successfully',
    'all_groups_fetched_successfully' => 'All groups fetched successfully',
    'latest_groups_posts_fetched_successfully' => 'Latest groups and random posts fetched successfully',
    'error_fetching_data' => 'An error occurred while fetching data',
    'group_invitations_fetched_successfully' => 'Group invitations fetched successfully',
    'doctor_not_found' => 'Doctor not found',
    'error_fetching_group_invitations' => 'An error occurred while fetching group invitations',

    // Push Notifications
    'new_comment_added' => 'New Comment was added 📣',
    'doctor_commented_on_post' => 'Dr. :name commented on your post',
    'new_like_added' => 'New Like was added ❤️',
    'doctor_liked_post' => 'Dr. :name liked your post',
    'new_invitation_created' => 'New Invitation was created 📣',
    'doctor_invited_to_group' => 'Dr. :name invited you to his group',
    'group_invitation_accepted' => 'Group Invitation Accepted 🎉',
    'doctor_accepted_invitation' => 'Dr. :name accepted your group invitation',
    'new_join_request' => 'New Join Request 📣',
    'doctor_requested_to_join' => 'Dr. :name requested to join group',
    'post_was_liked' => 'Post was liked 📣',
    'comment_was_liked' => 'Comment was liked 👍',
    'doctor_liked_comment' => 'Dr. :name liked your comment',
    'new_patient_comment' => 'New Patient Comment 💬',
    'doctor_commented_on_patient' => 'Dr. :name commented on your patient',

    // App Update Notifications
    'app_update_title' => 'EgyAkin v1.0.9 is Here! ✨',
    'app_update_body' => 'Kidney community is here! Post, explore #DialysisSupport, join groups, and enjoy a smoother experience.🔄 Update now for the latest features! 🚀',

    // Syndicate Card Notifications
    'syndicate_card_pending_approval' => 'New Syndicate Card Pending Approval 📋',
    'doctor_uploaded_syndicate_card' => 'Dr. :name has uploaded a new Syndicate Card for approval.',
    'syndicate_card_rejected' => 'Syndicate Card Rejected ❌',
    'syndicate_card_rejected_message' => 'Your Syndicate Card was rejected. Please upload the correct one.',
    'syndicate_card_approved' => 'Syndicate Card Approved ✅',
    'syndicate_card_approved_message' => 'Congratulations! 🎉 Your Syndicate Card has been approved.',

    // Patient Notifications
    'new_patient_created' => 'New Patient Created 🏥',
    'doctor_added_new_patient' => 'Dr. :name added a new patient: :patient',
    'outcome_submitted' => 'Outcome Submitted ✅',
    'doctor_submitted_outcome' => 'Dr. :name submitted outcome for: :patient',

    // FCM Token Management
    'fcm_token_stored_successfully' => 'FCM token stored successfully',
    'fcm_token_already_exists' => 'The FCM token already exists.',
    'failed_to_store_fcm_token' => 'Failed to store FCM token. Please try again later.',
    'failed_to_fetch_notifications' => 'Failed to fetch notifications',
    'failed_to_fetch_new_notifications' => 'Failed to fetch new notifications',

    // General API Messages
    'points_awarded' => 'Points awarded successfully',

    // Database Notification Messages
    'notification_post_liked' => 'Dr. :name liked your post',
    'notification_post_commented' => 'Dr. :name commented on your post',
    'notification_comment_liked' => 'Dr. :name liked your comment',
    'notification_group_post_created' => 'Dr. :name posted in your group',
    'notification_post_created' => 'Dr. :name added a new post',
    'notification_group_invitation' => 'Dr. :name invited you to his group',
    'notification_group_invitation_accepted' => 'Dr. :name accepted your group invitation',
    'notification_group_join_request' => 'Dr. :name requested to join group',
    'notification_new_patient' => 'Dr. :name created a new patient: :patient',
    'notification_outcome_created' => 'Outcome was created',
    'notification_new_comment' => 'New comment was created',
    'notification_consultation_request' => 'Dr. :name is seeking your advice for his patient',
    'notification_consultation_reply' => 'Dr. :name has replied to your consultation request. 📩',
    'notification_syndicate_card_status' => ':message',

    // Notification API Messages
    'notifications_retrieved_successfully' => 'Notifications retrieved successfully',
    'new_notifications_retrieved_successfully' => 'New notifications retrieved successfully',
    'notification_marked_as_read' => 'Notification marked as read',
    'notification_not_found' => 'Notification not found',
    'failed_to_mark_notification_as_read' => 'Failed to mark notification as read',
    'all_notifications_marked_as_read' => 'All notifications marked as read',
    'no_notifications_to_mark' => 'No notifications to mark as read',
    'failed_to_mark_all_notifications_as_read' => 'Failed to mark all notifications as read',

    // Notification Controller Messages
    'message_sent_successfully' => 'Message sent successfully',
    'no_tokens_found' => 'No tokens found',
    'message_sent_to_all_tokens' => 'Message sent successfully to all tokens',
    'failed_to_send_message' => 'Failed to send message. Please try again later.',
    'no_fcm_tokens_found' => 'No FCM tokens found.',

    // Consultation Messages
    'new_consultation_request_created' => 'New consultation request was created 📣',
    'doctor_seeking_advice' => 'Dr. :name is seeking your advice for his patient',
    'new_reply_on_consultation' => 'New Reply on Consultation Request 🔔',
    'doctor_replied_to_consultation' => 'Dr. :name has replied to your consultation request. 📩',

    // Test Messages
    'test_localized_notification_created' => 'Test localized notification created successfully',
    'failed_to_create_test_notification' => 'Failed to create test notification',

    // Group Messages
    'invitation_status_updated' => 'Invitation :status successfully',
    'joined_group_successfully' => 'Joined group successfully',
    'join_request_sent' => 'Join request sent, waiting for approval',

    // Upload Messages
    'header_picture_upload_failed' => 'Header picture upload failed.',
    'group_image_upload_failed' => 'Group image upload failed.',

    // Error Messages
    'group_not_found' => 'Group not found',
    'doctor_not_found' => 'Doctor not found',
    'post_creation_failed' => 'Post creation failed',
    'media_upload_failed' => 'Media upload failed.',

    // Notification Service Messages
    'message_sent_successfully_service' => 'Message sent successfully',
    'no_tokens_found_service' => 'No tokens found',
    'no_valid_tokens_found' => 'No valid tokens found',
    'notification_created_successfully' => 'Notification created successfully',
    'notification_not_found' => 'Notification not found',
    'notification_updated_successfully' => 'Notification updated successfully',
    'all_notifications_marked_as_read' => 'All notifications marked as read',
    'notification_deleted_successfully' => 'Notification deleted successfully',
    'invalid_fcm_token_format' => 'Invalid FCM token format.',
    'invalid_device_id_format' => 'Invalid device ID format.',
    'failed_to_store_fcm_token' => 'Failed to store FCM token.',
    'token_or_device_id_required' => 'Either token or device ID must be provided',

    // Patient Controller Messages
    'failed_to_retrieve_all_patients' => 'Failed to retrieve all patients for doctor.',
    'failed_to_retrieve_current_doctor_patients' => 'Failed to retrieve current doctor patients.',
    'failed_to_retrieve_doctor_profile_patients' => 'Failed to retrieve doctor profile patients.',

    // GFR (Glomerular Filtration Rate) Messages
    'current_GFR' => 'Current GFR',
    'basal_creatinine_GFR' => 'Basal Creatinine GFR',
    'creatinine_on_discharge_GFR' => 'Creatinine on Discharge GFR',
];
