<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($_SESSION['lang'])) $_SESSION['lang'] = 'en';
if (isset($_GET['lang'])) {
  $_SESSION['lang'] = ($_GET['lang'] === 'ar') ? 'ar' : 'en';
}

$texts = [
  'en' => [
    'site_title' => 'Whoiz.me – Smart QR Profiles',
    'welcome' => 'Welcome',
    'logout' => 'Logout',
    'login' => 'Login',
    'register' => 'Register',
    'dashboard' => 'Dashboard',
    'edit_profile' => 'Edit Profile',
    'dynamic_qr' => 'Dynamic QR',
    'stats' => 'Statistics',
    'qr_code' => 'QR Code',
    'language' => 'Language',
    'name' => 'Name',
    'email' => 'Email',
    'password' => 'Password',
    'confirm_password' => 'Confirm Password',
    'save' => 'Save',
    'saved_success' => 'Saved successfully',
    'create_account' => 'Create an account',
    'have_account' => 'Already have an account?',
    'no_account' => "Don't have an account?",
    'login_success' => 'Logged in successfully',
    'invalid_credentials' => 'Invalid login credentials',
    'email_taken' => 'Email already registered',
    'username' => 'Username',
    'display_name' => 'Display name',
    'bio' => 'Bio',
    'avatar_url' => 'Avatar URL',
    'website' => 'Website',
    'social_links' => 'Social Links',
    'whatsapp' => 'WhatsApp',
    'instagram' => 'Instagram',
    'twitter' => 'Twitter',
    'linkedin' => 'LinkedIn',
    'go_back' => 'Go back',
  ],
  'ar' => [
    'site_title' => 'هوويز.مي – بروفايلات QR ذكية',
    'welcome' => 'مرحبًا',
    'logout' => 'تسجيل خروج',
    'login' => 'تسجيل الدخول',
    'register' => 'تسجيل',
    'dashboard' => 'لوحة التحكم',
    'edit_profile' => 'تعديل البروفايل',
    'dynamic_qr' => 'ديناميك QR',
    'stats' => 'الإحصائيات',
    'qr_code' => 'كود QR',
    'language' => 'اللغة',
    'name' => 'الاسم',
    'email' => 'الإيميل',
    'password' => 'الباسورد',
    'confirm_password' => 'تأكيد الباسورد',
    'save' => 'حفظ',
    'saved_success' => 'تم الحفظ بنجاح',
    'create_account' => 'إنشاء حساب',
    'have_account' => 'عندك حساب؟',
    'no_account' => 'لسه ماعندكش حساب؟',
    'login_success' => 'تم تسجيل الدخول بنجاح',
    'invalid_credentials' => 'بيانات الدخول غير صحيحة',
    'email_taken' => 'الإيميل مسجّل بالفعل',
    'username' => 'اسم المستخدم',
    'display_name' => 'الاسم المعروض',
    'bio' => 'نبذة',
    'avatar_url' => 'رابط الصورة',
    'website' => 'الموقع',
    'social_links' => 'روابط التواصل',
    'whatsapp' => 'واتساب',
    'instagram' => 'إنستجرام',
    'twitter' => 'تويتر/X',
    'linkedin' => 'لينكدإن',
    'go_back' => 'رجوع',
  ],
];

function __t($key) {
  global $texts;
  $lang = $_SESSION['lang'] ?? 'en';
  return $texts[$lang][$key] ?? $key;
}