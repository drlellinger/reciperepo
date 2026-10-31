<?php
declare(strict_types=1);

$environmentValue = static function (string $name, mixed $default = null): mixed {
    $value = getenv($name);
    return $value === false || trim($value) === '' ? $default : $value;
};

return [
    'site_name' => $environmentValue('RECIPEREPO_SITE_NAME', 'reciperepo'),
    'logo_text' => $environmentValue('RECIPEREPO_LOGO_TEXT', 'R'),
    'logo_image' => $environmentValue('RECIPEREPO_LOGO_IMAGE', '/assets/logo.svg'),
    'logo_alt' => $environmentValue('RECIPEREPO_LOGO_ALT', 'reciperepo'),
    'favicon' => $environmentValue('RECIPEREPO_FAVICON', '/assets/favicon.ico'),
    'apple_touch_icon' => $environmentValue('RECIPEREPO_APPLE_TOUCH_ICON', '/assets/apple-touch-icon.png'),
    'site_url' => $environmentValue('RECIPEREPO_SITE_URL'),
    'footer_name' => $environmentValue('RECIPEREPO_FOOTER_NAME', '[Github](https://github.com/drlellinger/reciperepo)'),
    'privacy_url' => $environmentValue('RECIPEREPO_PRIVACY_URL'),
    'imprint_url' => $environmentValue('RECIPEREPO_IMPRINT_URL'),
];