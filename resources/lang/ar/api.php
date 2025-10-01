<?php

return [
    // Authentication Messages
    'login_success' => 'تم تسجيل الدخول بنجاح',
    'login_failed' => 'بيانات الدخول غير صحيحة',
    'logout_success' => 'تم تسجيل الخروج بنجاح',
    'registration_success' => 'تم التسجيل بنجاح',
    'registration_failed' => 'فشل في التسجيل',
    'email_verification_sent' => 'تم إرسال رابط التحقق من البريد الإلكتروني',
    'email_verified' => 'تم التحقق من البريد الإلكتروني بنجاح',
    'password_reset_sent' => 'تم إرسال رابط إعادة تعيين كلمة المرور',
    'password_reset_success' => 'تم إعادة تعيين كلمة المرور بنجاح',

    // User Management
    'user_created' => 'تم إنشاء المستخدم بنجاح',
    'user_updated' => 'تم تحديث المستخدم بنجاح',
    'user_deleted' => 'تم حذف المستخدم بنجاح',
    'user_not_found' => 'المستخدم غير موجود',
    'profile_updated' => 'تم تحديث الملف الشخصي بنجاح',

    // Patient Management
    'patient_created' => 'تم إنشاء المريض بنجاح',
    'patient_updated' => 'تم تحديث بيانات المريض بنجاح',
    'patient_deleted' => 'تم حذف المريض بنجاح',
    'patient_not_found' => 'المريض غير موجود',
    'patient_status_updated' => 'تم تحديث حالة المريض بنجاح',

    // Consultation & Assessment
    'consultation_created' => 'تم إنشاء الاستشارة بنجاح',
    'consultation_updated' => 'تم تحديث الاستشارة بنجاح',
    'assessment_completed' => 'تم إكمال التقييم بنجاح',
    'outcome_recorded' => 'تم تسجيل النتيجة بنجاح',
    'section_completed' => 'تم إكمال القسم بنجاح',

    // Notifications & Communications
    'notification_sent' => 'تم إرسال الإشعار بنجاح',
    'email_sent' => 'تم إرسال البريد الإلكتروني بنجاح',
    'reminder_sent' => 'تم إرسال التذكير بنجاح',
    'contact_request_sent' => 'تم إرسال طلب التواصل بنجاح',

    // Scoring & Achievements
    'points_awarded' => 'تم منح النقاط بنجاح',
    'milestone_reached' => 'مبروك! لقد وصلت إلى :points نقطة',
    'score_updated' => 'تم تحديث النتيجة بنجاح',

    // Data & Reports
    'report_generated' => 'تم إنشاء التقرير بنجاح',
    'data_exported' => 'تم تصدير البيانات بنجاح',
    'analytics_updated' => 'تم تحديث التحليلات بنجاح',

    // General Messages
    'success' => 'تمت العملية بنجاح',
    'error' => 'حدث خطأ',
    'validation_failed' => 'فشل في التحقق من البيانات',
    'unauthorized' => 'غير مصرح بالوصول',
    'forbidden' => 'الوصول محظور',
    'not_found' => 'المورد غير موجود',
    'server_error' => 'خطأ في الخادم الداخلي',
    'maintenance_mode' => 'النظام تحت الصيانة',

    // File Operations
    'file_uploaded' => 'تم رفع الملف بنجاح',
    'file_deleted' => 'تم حذف الملف بنجاح',
    'invalid_file_type' => 'نوع الملف غير صالح',
    'file_too_large' => 'حجم الملف كبير جداً',

    // Permissions & Roles
    'permission_granted' => 'تم منح الإذن',
    'permission_denied' => 'تم رفض الإذن',
    'role_assigned' => 'تم تعيين الدور بنجاح',
    'role_removed' => 'تم إزالة الدور بنجاح',

    // OTP & Verification
    'otp_sent' => 'تم إرسال رمز التحقق بنجاح',
    'otp_verified' => 'تم التحقق من الرمز بنجاح',
    'otp_expired' => 'انتهت صلاحية رمز التحقق',
    'otp_invalid' => 'رمز التحقق غير صحيح',
    'verification_required' => 'مطلوب التحقق من البريد الإلكتروني',

    // API Specific
    'invalid_request' => 'تنسيق الطلب غير صالح',
    'missing_parameters' => 'معاملات مطلوبة مفقودة',
    'rate_limit_exceeded' => 'تم تجاوز حد المعدل',
    'api_version_deprecated' => 'إصدار API مهجور',

    // Auth & User Management
    'user_created_successfully' => 'تم إنشاء المستخدم بنجاح',
    'user_logged_in_successfully' => 'تم تسجيل دخول المستخدم بنجاح',
    'user_logged_out_successfully' => 'تم تسجيل خروج المستخدم بنجاح',
    'user_updated_successfully' => 'تم تحديث المستخدم بنجاح',
    'user_deleted_successfully' => 'تم حذف المستخدم بنجاح',
    'registration_failed' => 'فشل في التسجيل',
    'invalid_credentials' => 'بيانات الدخول غير صحيحة',
    'too_many_login_attempts' => 'محاولات دخول كثيرة جداً. يرجى المحاولة مرة أخرى لاحقاً.',
    'account_blocked' => 'تم حظر حسابك. يرجى التواصل مع الدعم الفني.',
    'unauthorized_action' => 'غير مخول لك القيام بهذا الإجراء.',
    'current_password_incorrect' => 'كلمة المرور الحالية غير صحيحة',
    'password_changed_successfully' => 'تم تغيير كلمة المرور بنجاح',
    'profile_image_uploaded_successfully' => 'تم رفع صورة الملف الشخصي بنجاح.',
    'syndicate_card_uploaded_successfully' => 'تم رفع بطاقة النقابة بنجاح.',
    'no_user_found' => 'لم يتم العثور على مستخدم',
    'failed_to_retrieve_patients' => 'فشل في استرداد المرضى',
    'failed_to_retrieve_score_history' => 'فشل في استرداد تاريخ النقاط.',

    // Patient Management
    'patient_created_successfully' => 'تم إنشاء المريض بنجاح',
    'section_updated_successfully' => 'تم تحديث القسم بنجاح.',
    'patient_deleted_successfully' => 'تم حذف المريض والبيانات المرتبطة بنجاح',
    'section_not_found' => 'القسم غير موجود',
    'patient_not_found' => 'المريض غير موجود',
    'error_occurred' => 'خطأ: :message',

    // Contact Management
    'contact_created_successfully' => 'تم إنشاء جهة الاتصال بنجاح',
    'contact_updated_successfully' => 'تم تحديث جهة الاتصال بنجاح',
    'no_contact_found' => 'لم يتم العثور على جهة اتصال',

    // Achievement Management
    'achievement_creation_error' => 'حدث خطأ أثناء إنشاء الإنجاز',
    'achievement_retrieval_error' => 'حدث خطأ أثناء استرداد الإنجاز',

    // Consultation Management
    'consultation_created_successfully' => 'تم إنشاء الاستشارة بنجاح',
    'consultation_not_found' => 'الاستشارة غير موجودة.',
    'cannot_reply_closed_consultation' => 'لا يمكن الرد على استشارة مغلقة.',
    'consultation_updated_successfully' => 'تم تحديث طلب الاستشارة بنجاح',
    'cannot_add_doctors_closed_consultation' => 'لا يمكن إضافة أطباء إلى استشارة مغلقة.',
    'doctors_already_in_consultation' => 'جميع الأطباء المحددين موجودون بالفعل في هذه الاستشارة.',
    'doctors_added_successfully' => 'تم إضافة الأطباء إلى الاستشارة بنجاح.',
    'consultation_not_authorized' => 'الاستشارة غير موجودة أو أنت غير مخول لتعديلها.',
    'failed_to_add_doctors' => 'فشل في إضافة الأطباء إلى الاستشارة.',
    'failed_to_update_consultation_status' => 'فشل في تحديث حالة الاستشارة.',
    'not_authorized_view_consultation' => 'أنت غير مخول لعرض هذه الاستشارة.',
    'failed_to_retrieve_consultation_members' => 'فشل في استرداد أعضاء الاستشارة.',
    'cannot_reply_closed_consultation' => 'لا يمكن الرد على استشارة مغلقة.',
    'not_authorized_reply_consultation' => 'أنت غير مخول للرد على هذه الاستشارة.',
    'reply_added_successfully' => 'تم إضافة الرد بنجاح.',
    'failed_to_add_reply' => 'فشل في إضافة الرد.',
    'not_authorized_remove_doctors' => 'أنت غير مخول لإزالة الأطباء من هذه الاستشارة.',
    'cannot_remove_consultation_creator' => 'لا يمكن إزالة منشئ الاستشارة.',

    // Email & Notifications
    'user_not_authenticated' => 'المستخدم غير مصادق عليه',
    'verification_email_sent_successfully' => 'تم إرسال بريد التحقق بنجاح',
    'failed_to_send_verification_email' => 'فشل في إرسال بريد التحقق',
    'invalid_or_expired_verification_link' => 'رابط التحقق غير صالح أو منتهي الصلاحية',
    'email_verified_successfully' => 'تم التحقق من البريد الإلكتروني بنجاح',
    'new_contact_request' => 'طلب تواصل جديد',
    'reminder_from_egyakin' => 'تذكير من EGYAKIN',

    // Mail Content
    'hello_doctor' => 'مرحباً دكتور :name',
    'patient_outcome_not_submitted' => 'نتيجة المريض ":patient" لم يتم تقديمها بعد، يرجى تحديثها الآن.',
    'patient_added_since' => 'تم إضافة مريضك منذ :date',
    'thank_you_using_application' => 'شكراً لك لاستخدام تطبيقنا!',
    'sincerely' => 'مع أطيب التحيات،',
    'egyakin_scientific_team' => 'فريق EGYAKIN العلمي.',
    'urgent_action_required' => 'مطلوب إجراء عاجل',
    'patient_outcome_pending_message' => 'لم يتم تقديم نتيجة المريض بعد. يرجى تحديثها فوراً لضمان توثيق رعاية المريض بشكل صحيح.',
    'patient_information' => 'معلومات المريض',
    'patient_name' => 'اسم المريض',
    'added_since' => 'مضاف منذ',
    'status' => 'الحالة',
    'outcome_pending' => 'النتيجة معلقة',
    'quality_care_commitment' => 'كجزء من التزامنا بجودة رعاية المرضى، نحتاج للتأكد من توثيق وتقديم جميع نتائج المرضى بشكل صحيح. هذا يساعد في الحفاظ على سجلات طبية دقيقة وتحسين جودة رعاية المرضى.',
    'thank_you_attention' => 'شكراً لك على اهتمامك بهذا الأمر ولاستخدام EGYAKIN! 🚀',
    'best_regards' => 'مع أطيب التحيات،',
    'automated_reminder' => 'هذا تذكير تلقائي. يرجى التأكد من تقديم نتائج المرضى بسرعة للحفاظ على معايير الرعاية الجيدة.',
    'unknown_patient' => 'مريض غير معروف',

    // Contact Request Content
    'hello_doctor_mostafa' => 'مرحباً دكتور مصطفى',
    'contact_message' => '<< :message >>',
    'contact_reach_info' => 'يمكن الوصول إليه عبر البريد الإلكتروني: :email أو الهاتف: :phone',

    // Test & Debug
    'test_email_subject' => 'اختبار بريد EGYAKIN - :timestamp',
    'test_email_body' => 'هذا بريد إلكتروني تجريبي من تطبيق EGYAKIN للتحقق من إعدادات البريد.',
    'weekly_summary_subject' => 'ملخص EGYAKIN الأسبوعي - :week_start - :week_end',

    // Post & Feed Management
    'no_feed_posts_found' => 'لم يتم العثور على منشورات في الخلاصة',
    'feed_posts_retrieved_successfully' => 'تم استرداد منشورات الخلاصة بنجاح',
    'error_retrieving_feed_posts' => 'حدث خطأ أثناء استرداد منشورات الخلاصة',
    'post_comments_retrieved_successfully' => 'تم استرداد تعليقات المنشور بنجاح',
    'error_retrieving_post_comments' => 'حدث خطأ أثناء استرداد تعليقات المنشور',
    'post_created_successfully' => 'تم إنشاء المنشور بنجاح',
    'error_creating_post' => 'خطأ في إنشاء المنشور: :message',
    'post_updated_successfully' => 'تم تحديث المنشور بنجاح',
    'post_not_found' => 'المنشور غير موجود',
    'error_updating_post' => 'حدث خطأ أثناء تحديث المنشور: :message',
    'comment_added_successfully' => 'تم إضافة التعليق بنجاح',
    'error_adding_comment' => 'حدث خطأ أثناء إضافة التعليق',
    'post_liked_successfully' => 'تم إعجاب المنشور بنجاح',
    'post_unliked_successfully' => 'تم إلغاء إعجاب المنشور بنجاح',
    'error_liking_post' => 'حدث خطأ أثناء الإعجاب/إلغاء الإعجاب بالمنشور',
    'post_saved_successfully' => 'تم حفظ المنشور بنجاح',
    'post_unsaved_successfully' => 'تم إلغاء حفظ المنشور بنجاح',
    'error_saving_post' => 'حدث خطأ أثناء حفظ/إلغاء حفظ المنشور',
    'post_deleted_successfully' => 'تم حذف المنشور بنجاح',
    'error_deleting_post' => 'حدث خطأ أثناء حذف المنشور',
    'comment_liked_successfully' => 'تم إعجاب التعليق بنجاح',
    'comment_unliked_successfully' => 'تم إلغاء إعجاب التعليق بنجاح',
    'error_liking_comment' => 'حدث خطأ أثناء الإعجاب/إلغاء الإعجاب بالتعليق',

    // Group Management
    'header_picture_upload_failed' => 'فشل في رفع صورة الرأس.',
    'group_image_upload_failed' => 'فشل في رفع صورة المجموعة.',
    'group_created_successfully' => 'تم إنشاء المجموعة بنجاح',
    'error_creating_group' => 'حدث خطأ أثناء إنشاء المجموعة',
    'group_updated_successfully' => 'تم تحديث المجموعة بنجاح',
    'group_not_found' => 'المجموعة غير موجودة',
    'validation_failed' => 'فشل التحقق من الصحة',
    'error_updating_group' => 'حدث خطأ أثناء تحديث المجموعة',
    'group_deleted_successfully' => 'تم حذف المجموعة بنجاح',
    'invitations_processed' => 'تم معالجة الدعوات',
    'error_processing_invitations' => 'حدث خطأ أثناء معالجة الدعوات',
    'invalid_invitation' => 'دعوة غير صالحة',
    'error_handling_invitation' => 'حدث خطأ أثناء التعامل مع الدعوة',
    'group_details_retrieved_successfully' => 'تم استرداد تفاصيل المجموعة بنجاح',
    'member_not_found_in_group' => 'العضو غير موجود في المجموعة',
    'member_removed_successfully' => 'تم إزالة العضو بنجاح',
    'members_search_results' => 'نتائج البحث عن الأعضاء',
    'community_members_fetched_successfully' => 'تم جلب أعضاء المجتمع والدعوات المعلقة بنجاح',
    'error_fetching_members_invitations' => 'حدث خطأ أثناء جلب الأعضاء والدعوات',
    'group_details_posts_fetched_successfully' => 'تم جلب تفاصيل المجموعة مع المنشورات المقسمة بنجاح',
    'already_member_of_group' => 'أنت عضو بالفعل في هذه المجموعة',
    'not_member_of_group' => 'أنت لست عضواً في هذه المجموعة',
    'left_group_successfully' => 'تم ترك المجموعة بنجاح',
    'user_groups_fetched_successfully' => 'تم جلب مجموعات المستخدم بنجاح',
    'all_groups_fetched_successfully' => 'تم جلب جميع المجموعات بنجاح',
    'latest_groups_posts_fetched_successfully' => 'تم جلب أحدث المجموعات والمنشورات العشوائية بنجاح',
    'error_fetching_data' => 'حدث خطأ أثناء جلب البيانات',
    'group_invitations_fetched_successfully' => 'تم جلب دعوات المجموعة بنجاح',
    'doctor_not_found' => 'الطبيب غير موجود',
    'error_fetching_group_invitations' => 'حدث خطأ أثناء جلب دعوات المجموعة',

    // Push Notifications
    'new_comment_added' => 'تم إضافة تعليق جديد 📣',
    'new_like_added' => 'تم إضافة إعجاب جديد ❤️',
    'new_invitation_created' => 'تم إنشاء دعوة جديدة 📣',
    'group_invitation_accepted' => 'تم قبول دعوة المجموعة 🎉',
    'new_join_request' => 'طلب انضمام جديد 📣',
    'post_was_liked' => 'تم إعجاب المنشور 📣',
    'comment_was_liked' => 'تم إعجاب التعليق 👍',
    'new_patient_comment' => 'تعليق مريض جديد 💬',

    // App Update Notifications
    'app_update_title' => 'EgyAkin v1.0.9 متوفر الآن! ✨',
    'app_update_body' => 'مجتمع الكلى هنا! انشر، استكشف #DialysisSupport، انضم للمجموعات، واستمتع بتجربة أكثر سلاسة.🔄 حدث الآن للحصول على أحدث الميزات! 🚀',

    // Syndicate Card Notifications
    'syndicate_card_pending_approval' => 'بطاقة نقابة جديدة في انتظار الموافقة 📋',
    'syndicate_card_rejected' => 'تم رفض بطاقة النقابة ❌',
    'syndicate_card_rejected_message' => 'تم رفض بطاقة النقابة الخاصة بك. يرجى رفع البطاقة الصحيحة.',
    'syndicate_card_approved' => 'تم اعتماد بطاقة النقابة ✅',
    'syndicate_card_approved_message' => 'مبروك! 🎉 تم اعتماد بطاقة النقابة الخاصة بك.',

    // Patient Notifications
    'new_patient_created' => 'تم إنشاء مريض جديد 🏥',
    'outcome_submitted' => 'تم تقديم النتيجة ✅',
    'notification_new_patient_clean' => ':name أنشأ مريضاً جديداً: :patient',
    'notification_outcome_submitted_clean' => ':name قدم نتيجة لـ: :patient',

    // FCM Token Management
    'fcm_token_stored_successfully' => 'تم حفظ رمز FCM بنجاح',
    'fcm_token_already_exists' => 'رمز FCM موجود بالفعل.',
    'failed_to_store_fcm_token' => 'فشل في حفظ رمز FCM. يرجى المحاولة مرة أخرى لاحقاً.',
    'failed_to_fetch_notifications' => 'فشل في جلب الإشعارات',
    'failed_to_fetch_new_notifications' => 'فشل في جلب الإشعارات الجديدة',

    // General API Messages
    'points_awarded' => 'تم منح النقاط بنجاح',

    // Database Notification Messages
    'notification_outcome_created' => 'تم إنشاء النتيجة',
    'notification_new_comment' => 'تم إنشاء تعليق جديد',
    'notification_syndicate_card_status' => ':message',

    // Notification API Messages
    'notifications_retrieved_successfully' => 'تم استرداد الإشعارات بنجاح',
    'new_notifications_retrieved_successfully' => 'تم استرداد الإشعارات الجديدة بنجاح',
    'notification_marked_as_read' => 'تم تمييز الإشعار كمقروء',
    'notification_not_found' => 'الإشعار غير موجود',
    'failed_to_mark_notification_as_read' => 'فشل في تمييز الإشعار كمقروء',
    'all_notifications_marked_as_read' => 'تم تمييز جميع الإشعارات كمقروءة',
    'no_notifications_to_mark' => 'لا توجد إشعارات لتمييزها كمقروءة',
    'failed_to_mark_all_notifications_as_read' => 'فشل في تمييز جميع الإشعارات كمقروءة',

    // Notification Controller Messages
    'message_sent_successfully' => 'تم إرسال الرسالة بنجاح',
    'no_tokens_found' => 'لم يتم العثور على رموز',
    'message_sent_to_all_tokens' => 'تم إرسال الرسالة بنجاح إلى جميع الرموز',
    'failed_to_send_message' => 'فشل في إرسال الرسالة. يرجى المحاولة مرة أخرى لاحقاً.',
    'no_fcm_tokens_found' => 'لم يتم العثور على رموز FCM.',

    // Consultation Messages
    'new_consultation_request_created' => 'تم إنشاء طلب استشارة جديد 📣',
    'new_reply_on_consultation' => 'رد جديد على طلب استشارة 🔔',

    // Test Messages
    'test_localized_notification_created' => 'تم إنشاء إشعار اختباري مترجم بنجاح',
    'failed_to_create_test_notification' => 'فشل في إنشاء إشعار اختباري',

    // Group Messages
    'invitation_status_updated' => 'تم :status الدعوة بنجاح',
    'joined_group_successfully' => 'تم الانضمام للمجموعة بنجاح',
    'join_request_sent' => 'تم إرسال طلب الانضمام، في انتظار الموافقة',

    // Upload Messages
    'header_picture_upload_failed' => 'فشل تحميل صورة الرأس.',
    'group_image_upload_failed' => 'فشل تحميل صورة المجموعة.',

    // Error Messages
    'group_not_found' => 'المجموعة غير موجودة',
    'doctor_not_found' => 'الطبيب غير موجود',
    'post_creation_failed' => 'فشل في إنشاء المنشور',
    'media_upload_failed' => 'فشل تحميل الوسائط.',

    // Notification Service Messages
    'message_sent_successfully_service' => 'تم إرسال الرسالة بنجاح',
    'no_tokens_found_service' => 'لم يتم العثور على رموز',
    'no_valid_tokens_found' => 'لم يتم العثور على رموز صالحة',
    'notification_created_successfully' => 'تم إنشاء الإشعار بنجاح',
    'notification_not_found' => 'الإشعار غير موجود',
    'notification_updated_successfully' => 'تم تحديث الإشعار بنجاح',
    'all_notifications_marked_as_read' => 'تم تمييز جميع الإشعارات كمقروءة',
    'notification_deleted_successfully' => 'تم حذف الإشعار بنجاح',
    'invalid_fcm_token_format' => 'تنسيق رمز FCM غير صالح.',
    'invalid_device_id_format' => 'تنسيق معرف الجهاز غير صالح.',
    'failed_to_store_fcm_token' => 'فشل في حفظ رمق FCM.',
    'token_or_device_id_required' => 'يجب توفير الرمز أو معرف الجهاز',

    // Patient Controller Messages
    'failed_to_retrieve_all_patients' => 'فشل في استرداد جميع المرضى للطبيب.',
    'failed_to_retrieve_current_doctor_patients' => 'فشل في استرداد مرضى الطبيب الحالي.',
    'failed_to_retrieve_doctor_profile_patients' => 'فشل في استرداد مرضى ملف الطبيب.',

    // GFR (Glomerular Filtration Rate) Messages
    'current_GFR' => 'معدل الترشيح الكلوي الحالي',
    'basal_creatinine_GFR' => 'معدل الترشيح الكلوي للكرياتينين الأساسي',
    'creatinine_on_discharge_GFR' => 'معدل الترشيح الكلوي للكرياتينين عند الخروج',

    // Consultation Messages
    'consultation_unauthorized_patient' => 'يمكنك فقط طلب استشارة للمرضى الذين قمت بإنشائهم.',
    'consultation_closed' => 'لا يمكن الرد على استشارة مغلقة.',
    'consultation_unauthorized_reply' => 'غير مخول لك الرد على هذه الاستشارة.',
    'consultation_reply_failed' => 'فشل في إضافة الرد.',
    'reply_required' => 'الرد مطلوب.',
    'reply_string' => 'يجب أن يكون الرد نص.',
    'patient_not_exist' => 'المريض المحدد غير موجود.',
    'patient_id_required' => 'معرف المريض مطلوب.',
    'consult_message_required' => 'رسالة الاستشارة مطلوبة.',
    'consult_message_string' => 'يجب أن تكون رسالة الاستشارة نص.',
    'consult_doctors_required' => 'مطلوب طبيب واحد على الأقل للاستشارة.',
    'consult_doctors_array' => 'يجب تقديم الأطباء المستشارين كمصفوفة.',
    'consult_doctors_not_exist' => 'واحد أو أكثر من الأطباء المحددين غير موجود.',
    'status_required' => 'الحالة مطلوبة.',
    'status_boolean' => 'يجب أن تكون الحالة صحيح (مفتوح) أو خطأ (مغلق).',
    'consult_doctors_min' => 'مطلوب طبيب واحد على الأقل للاستشارة.',
    'private_group_access_denied' => 'تم رفض الوصول. يجب أن تكون عضواً في هذه المجموعة الخاصة لتنفيذ هذا الإجراء.',

    // Analytics Dashboard
    'analytics_title' => 'لوحة تحليلات EGYAKIN',
    'analytics_subtitle' => 'تحليلات بيانات طبية شاملة',
    'data_use_warning' => 'استخدام البيانات غير مسموح دون موافقتنا.',
    'data_use_warning_ar' => 'لا يُسمح باستخدام البيانات دون موافقتنا.',
    'real_time_insights' => 'رؤى البيانات في الوقت الفعلي',
    'total_doctors' => 'إجمالي الأطباء',
    'total_users' => 'إجمالي المستخدمين',
    'total_patients' => 'إجمالي المرضى',
    'male_patients' => 'المرضى الذكور',
    'female_patients' => 'المرضى الإناث',
    'verified' => 'مُتحقق منه',
    'non_verified' => 'غير مُتحقق منه',
    'active_only' => 'النشطون فقط',
    'gender_distribution' => 'توزيع الجنس',
    'dm_htn_statistics' => 'إحصائيات السكري مقابل ضغط الدم',
    'dialysis_percentage' => 'نسبة الغسيل الكلوي',
    'department_distribution' => 'توزيع الأقسام',
    'provisional_diagnosis' => 'التشخيص المبدئي',
    'cause_of_aki' => 'سبب الفشل الكلوي الحاد',
    'patient_outcomes_status' => 'نتائج المرضى والحالة',
    'outcome_status' => 'حالة النتيجة',
    'submit_status' => 'حالة التسليم',
    'outcome_values' => 'قيم النتائج',
    'no_department_data' => 'لا توجد بيانات أقسام متاحة',
    'no_diagnosis_data' => 'لا توجد بيانات تشخيص متاحة',
    'no_aki_cause_data' => 'لا توجد بيانات أسباب الفشل الكلوي الحاد متاحة',
    'no_outcome_data' => 'لا توجد بيانات نتائج متاحة',
    'male' => 'ذكر',
    'female' => 'أنثى',
    'dm_yes' => 'السكري (نعم)',
    'dm_no' => 'السكري (لا)',
    'htn_yes' => 'ضغط الدم (نعم)',
    'htn_no' => 'ضغط الدم (لا)',
    'patient_count' => 'عدد المرضى',
    'toggle_dark_mode' => 'تبديل الوضع المظلم',
    'medical_analytics_footer' => 'لوحة التحليلات الطبية',
    'all_rights_reserved' => 'جميع الحقوق محفوظة.',

    // Group join request notifications
    'join_request_approved' => 'تم قبول طلب الانضمام بنجاح',
    'join_request_declined' => 'تم رفض طلب الانضمام',
    'join_request_approved_title' => 'تم قبول الطلب ✅',
    'join_request_declined_title' => 'تم رفض الطلب ❌',
    'join_request_approved_body' => 'تم قبول طلبك للانضمام إلى :group!',
    'join_request_declined_body' => 'تم رفض طلبك للانضمام إلى :group.',
    'notification_group_join_approved' => ':owner_name وافق على طلبك للانضمام إلى :group_name',
    'notification_group_join_declined' => ':owner_name رفض طلبك للانضمام إلى :group_name',
    'unauthorized_group_action' => 'غير مخول لك تنفيذ هذا الإجراء',
    'invalid_join_request' => 'طلب انضمام غير صحيح',
    'error_handling_join_request' => 'خطأ في معالجة طلب الانضمام',

    // Group member removal notifications
    'member_removed_title' => 'تم إزالتك من المجموعة 🚫',
    'member_removed_body' => 'تم إزالتك من المجموعة :group',
    'notification_group_member_removed' => ':remover_name قام بإزالتك من :group_name',

    // ========================================================================
    // مفاتيح الإشعارات النظيفة (بدون بادئة د. مُدمجة)
    // هذه المفاتيح تعمل مع LocalizedNotificationService لإضافة بادئة د. تلقائياً
    // ========================================================================

    // التواصل والاتصال
    'clean_contact_request_message' => ':name الذي يعمل في :workplace قد أرسل طلب تواصل جديد.',

    // التفاعلات الاجتماعية
    'clean_doctor_commented_on_post' => ':name علق على منشورك',
    'clean_doctor_liked_post' => ':name أعجب بمنشورك',
    'clean_doctor_invited_to_group' => ':name دعاك إلى مجموعته',
    'clean_doctor_accepted_invitation' => ':name قبل دعوتك للمجموعة',
    'clean_doctor_requested_to_join' => ':name طلب الانضمام إلى المجموعة',
    'clean_doctor_liked_comment' => ':name أعجب بتعليقك',
    'clean_doctor_commented_on_patient' => ':name علق على مريضك',

    // الإدارية
    'clean_doctor_uploaded_syndicate_card' => ':name قام برفع بطاقة نقابة جديدة للموافقة.',

    // إدارة المرضى
    'clean_doctor_added_new_patient' => ':name أضاف مريضاً جديداً: :patient',
    'clean_doctor_submitted_outcome' => ':name قدم نتيجة لـ: :patient',

    // قوالب الإشعارات
    'clean_notification_post_liked' => ':name أعجب بمنشورك',
    'clean_notification_post_commented' => ':name علق على منشورك',
    'clean_notification_comment_liked' => ':name أعجب بتعليقك',
    'clean_notification_group_post_created' => ':name نشر في مجموعتك',
    'clean_notification_post_created' => ':name أضاف منشوراً جديداً',
    'clean_notification_group_invitation' => ':name دعاك إلى مجموعته',
    'clean_notification_group_invitation_accepted' => ':name قبل دعوتك للمجموعة',
    'clean_notification_group_join_request' => ':name طلب الانضمام إلى المجموعة',
    'clean_notification_new_patient' => ':name أنشأ مريضاً جديداً: :patient',

    // الاستشارات
    'clean_notification_consultation_request' => ':name يطلب مشورتك لمريضه',
    'clean_notification_consultation_reply' => ':name رد على طلب استشارتك. 📩',
    'clean_doctor_seeking_advice' => ':name يطلب مشورتك لمريضه',
    'clean_doctor_replied_to_consultation' => ':name رد على طلب استشارتك. 📩',
];
