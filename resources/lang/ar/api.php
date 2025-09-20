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
    'contact_request_message' => 'د.:name الذي يعمل في :workplace قد أرسل طلب تواصل جديد.',
    'contact_message' => '<< :message >>',
    'contact_reach_info' => 'يمكن الوصول إليه عبر البريد الإلكتروني: :email أو الهاتف: :phone',

    // Test & Debug
    'test_email_subject' => 'اختبار بريد EGYAKIN - :timestamp',
    'test_email_body' => 'هذا بريد إلكتروني تجريبي من تطبيق EGYAKIN للتحقق من إعدادات البريد.',
    'weekly_summary_subject' => 'ملخص EGYAKIN الأسبوعي - :week_start - :week_end',
];
