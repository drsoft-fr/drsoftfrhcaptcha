<?php

global $_MODULE;
$_MODULE = [];

// Translations for back-office (Admin)
$_MODULE['<{drsoftfrhcaptcha}prestashop>drsoftfrhcaptcha'] = 'hCaptcha Protection';
$_MODULE['<{drsoftfrhcaptcha}prestashop>configure'] = 'hCaptcha Configuration';

// Configuration messages
$_MODULE['Modules.Drsoftfrhcaptcha.Admin'] = [
    'drSoft.fr - hCaptcha Protection' => 'drSoft.fr - hCaptcha Protection',
    'Protect your forms against bots with hCaptcha (GDPR compliant)' => 'Protect your forms against bots with hCaptcha (GDPR compliant)',
    'Are you sure you want to uninstall this module?' => 'Are you sure you want to uninstall this module?',
    'hCaptcha keys are required' => 'hCaptcha keys are required',
    'Configuration saved successfully' => 'Configuration saved successfully',
    'hCaptcha Configuration' => 'hCaptcha Configuration',
    'Site Key' => 'Site Key',
    'Get your keys at https://www.hcaptcha.com/' => 'Get your keys at https://www.hcaptcha.com/',
    'Secret Key' => 'Secret Key',
    'Never share this key publicly' => 'Never share this key publicly',
    'Theme' => 'Theme',
    'Light' => 'Light',
    'Dark' => 'Dark',
    'Size' => 'Size',
    'Normal' => 'Normal',
    'Compact' => 'Compact',
    'Enable on contact form' => 'Enable on contact form',
    'Enable on registration form' => 'Enable on registration form',
    'Enable on login form' => 'Enable on login form',
    'Optional - May affect user experience' => 'Optional - May affect user experience',
    'Enable on newsletter registration' => 'Enable on newsletter registration',
    'Yes' => 'Yes',
    'No' => 'No',
    'Save' => 'Save',
    'Advanced Settings - CSS Selectors' => 'Advanced Settings - CSS Selectors',
    'Customize CSS selectors if your theme uses non-standard form structures. Separate multiple selectors with commas. Leave empty to use default values.' => 'Customize CSS selectors if your theme uses non-standard form structures. Separate multiple selectors with commas. Leave empty to use default values.',
    'Login form selectors' => 'Login form selectors',
    'Contact form selectors' => 'Contact form selectors',
    'Submit button selectors' => 'Submit button selectors',
    'Default:' => 'Default:',
    'Login form selectors contain invalid characters' => 'Login form selectors contain invalid characters',
    'Contact form selectors contain invalid characters' => 'Contact form selectors contain invalid characters',
    'Submit button selectors contain invalid characters' => 'Submit button selectors contain invalid characters',
    'Configuration not saved. Please fix the errors above.' => 'Configuration not saved. Please fix the errors above.',
    'Site key format is invalid' => 'Site key format is invalid',
    'Secret key format is invalid' => 'Secret key format is invalid',
    'Warning: OpenSSL extension is not available. The secret key will be stored in plain text in the database, which is a security risk. Please enable the OpenSSL PHP extension.' => 'Warning: OpenSSL extension is not available. The secret key will be stored in plain text in the database, which is a security risk. Please enable the OpenSSL PHP extension.',
];

// Messages for front-office (Shop)
$_MODULE['Modules.Drsoftfrhcaptcha.Shop'] = [
    'Please complete the captcha' => 'Please complete the captcha',
    'Captcha verification failed. Please try again.' => 'Captcha verification failed. Please try again.',
    'Please complete the captcha before submitting the form.' => 'Please complete the captcha before submitting the form.',
    '[hCaptcha] Error:' => '[hCaptcha] Error:',
];
