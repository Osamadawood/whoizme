<?php
declare(strict_types=1);

/**
 * الحارس الافتراضي للصفحات “المحمية”.
 * الصفحات العامة لازم تعرّف SKIP_AUTH_GUARD قبل require bootstrap.
 */

// لو الصفحة عايزة حماية، استدعي require_login()
if (!defined('PAGE_PUBLIC')) {
    require_login();
}