<?php
/**
 * hCaptcha Module for PrestaShop
 * Form protection with hCaptcha (GDPR compliant)
 *
 * @author Dylan Ramos - drSoft.fr
 * @copyright 2026 drSoft.fr
 * @license MIT
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

class drsoftfrhcaptcha extends Module
{
    /**
     * Default CSS selectors for forms
     */
    public const DEFAULT_SELECTORS_LOGIN = '#login-form, .login-form form, form[action*="login"], form[id*="login"]';
    public const DEFAULT_SELECTORS_CONTACT = '.contact-form form, #contact-form, form[action*="contact"], form[id*="contact"], .contact-rich form';
    public const DEFAULT_SELECTORS_SUBMIT = 'button[type="submit"], input[type="submit"], .btn-primary, .form-control-submit';

    /**
     * hCaptcha key format validation
     * Site keys and secret keys are typically 40-50 alphanumeric characters with hyphens
     */
    private const HCAPTCHA_KEY_MIN_LENGTH = 30;
    private const HCAPTCHA_KEY_MAX_LENGTH = 100;

    /**
     * Module configuration keys with default values
     */
    private const CONFIG_KEYS = [
        'DRSOFT_FR_HCAPTCHA_SITE_KEY' => '',
        'DRSOFT_FR_HCAPTCHA_SECRET_KEY' => '',
        'DRSOFT_FR_HCAPTCHA_THEME' => 'light',
        'DRSOFT_FR_HCAPTCHA_SIZE' => 'normal',
        'DRSOFT_FR_HCAPTCHA_CONTACT_ENABLED' => '1',
        'DRSOFT_FR_HCAPTCHA_REGISTER_ENABLED' => '1',
        'DRSOFT_FR_HCAPTCHA_LOGIN_ENABLED' => '0',
        'DRSOFT_FR_HCAPTCHA_NEWSLETTER_ENABLED' => '0',
        'DRSOFT_FR_HCAPTCHA_SELECTORS_LOGIN' => self::DEFAULT_SELECTORS_LOGIN,
        'DRSOFT_FR_HCAPTCHA_SELECTORS_CONTACT' => self::DEFAULT_SELECTORS_CONTACT,
        'DRSOFT_FR_HCAPTCHA_SELECTORS_SUBMIT' => self::DEFAULT_SELECTORS_SUBMIT,
    ];

    /**
     * @var string $authorEmail Author email
     */
    public $authorEmail;

    /**
     * @var string $moduleGithubRepositoryUrl Module GitHub repository URL
     */
    public $moduleGithubRepositoryUrl;

    /**
     * @var string $moduleGithubIssuesUrl Module GitHub issues URL
     */
    public $moduleGithubIssuesUrl;

    /**
     * Module constructor
     */
    public function __construct()
    {
        $this->name = 'drsoftfrhcaptcha';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'drSoft.fr';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0',
            'max' => '9.99.99'
        ];
        $this->bootstrap = true;
        $this->authorEmail = 'contact@drsoft.fr';
        $this->moduleGithubRepositoryUrl = 'https://github.com/drsoft-fr/drsoftfrhcaptcha';
        $this->moduleGithubIssuesUrl = 'https://github.com/drsoft-fr/drsoftfrhcaptcha/issues';

        parent::__construct();

        $this->displayName = $this->trans('drSoft.fr - hCaptcha Protection', [], 'Modules.Drsoftfrhcaptcha.Admin');
        $this->description = $this->trans(
            'Protect your forms against bots with hCaptcha (GDPR compliant)',
            [],
            'Modules.Drsoftfrhcaptcha.Admin'
        );

        $this->confirmUninstall = $this->trans(
            'Are you sure you want to uninstall this module?',
            [],
            'Modules.Drsoftfrhcaptcha.Admin'
        );
    }

    /**
     * Module installation
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('displayCustomerAccountForm')
            && $this->registerHook('actionAuthenticationSubmitHCaptcha')
            && $this->registerHook('actionSubmitAccountBefore')
            && $this->registerHook('actionContactSubmitHCaptcha')
            && $this->registerHook('displayNewsletterRegistration')
            && $this->registerHook('actionNewsletterRegistrationBefore')
            && $this->installConfiguration();
    }

    /**
     * Install default configuration
     */
    private function installConfiguration(): bool
    {
        foreach (self::CONFIG_KEYS as $key => $value) {
            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Module uninstallation
     */
    public function uninstall()
    {
        // Delete all configuration keys
        foreach (array_keys(self::CONFIG_KEYS) as $key) {
            if (!Configuration::deleteByName($key)) {
                return false;
            }
        }

        return parent::uninstall();
    }

    /**
     * Module configuration page
     */
    public function getContent(): string
    {
        $output = '';

        // Security warning if OpenSSL is not available
        if (!function_exists('openssl_encrypt')) {
            $output .= $this->displayWarning(
                $this->trans(
                    'Warning: OpenSSL extension is not available. The secret key will be stored in plain text in the database, which is a security risk. Please enable the OpenSSL PHP extension.',
                    [],
                    'Modules.Drsoftfrhcaptcha.Admin'
                )
            );
        }

        // Form processing
        if (Tools::isSubmit('submit' . $this->name)) {
            $output .= $this->postProcess();
        }

        // Display form
        $output .= $this->displayConfigurationForm();

        $this->context->smarty->assign([
            'module' => $this,
            'logo' => _MODULE_DIR_ . 'drsoftfrhcaptcha/views/img/logo-dark-master.png',
        ]);

        $footer = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/_footer.tpl');

        return $output . $footer;
    }

    /**
     * Process configuration save
     */
    private function postProcess(): string
    {
        // Collect all values first
        $siteKey = trim((string)Tools::getValue('DRSOFT_FR_HCAPTCHA_SITE_KEY'));
        $secretKey = trim((string)Tools::getValue('DRSOFT_FR_HCAPTCHA_SECRET_KEY'));
        $allowedThemes = ['light', 'dark'];
        $allowedSizes = ['normal', 'compact'];
        $theme = Tools::getValue('DRSOFT_FR_HCAPTCHA_THEME');
        $size = Tools::getValue('DRSOFT_FR_HCAPTCHA_SIZE');
        $theme = in_array($theme, $allowedThemes, true) ? $theme : 'light';
        $size = in_array($size, $allowedSizes, true) ? $size : 'normal';

        // Validate ALL fields BEFORE saving anything
        $errors = [];

        // Validate required fields
        if (empty($siteKey) || empty($secretKey)) {
            $errors[] = $this->trans('hCaptcha keys are required', [], 'Modules.Drsoftfrhcaptcha.Admin');
        } else {
            // Validate site key format
            if (!$this->isValidHCaptchaKey($siteKey, 'site')) {
                $errors[] = $this->trans('Site key format is invalid', [], 'Modules.Drsoftfrhcaptcha.Admin');
            }

            // Check if secret key is the placeholder (not changed)
            $isPlaceholder = ($secretKey === '••••••••••••••••••••');
            $storedSecretKey = Configuration::get('DRSOFT_FR_HCAPTCHA_SECRET_KEY');

            // Decrypt stored key to compare
            $decryptedStoredKey = $this->decryptSecretKey($storedSecretKey);
            $secretKeyChanged = !$isPlaceholder && ($secretKey !== $decryptedStoredKey);

            // Only validate secret key format if it has changed
            if (!$isPlaceholder && $secretKeyChanged && !$this->isValidHCaptchaKey($secretKey, 'secret')) {
                $errors[] = $this->trans('Secret key format is invalid', [], 'Modules.Drsoftfrhcaptcha.Admin');
            }
        }

        // Validate advanced selectors
        $rawSelectorsLogin = (string)Tools::getValue('DRSOFT_FR_HCAPTCHA_SELECTORS_LOGIN');
        if (!$this->isValidSelectors($rawSelectorsLogin)) {
            $errors[] = $this->trans('Login form selectors contain invalid characters', [], 'Modules.Drsoftfrhcaptcha.Admin');
        }

        $rawSelectorsContact = (string)Tools::getValue('DRSOFT_FR_HCAPTCHA_SELECTORS_CONTACT');
        if (!$this->isValidSelectors($rawSelectorsContact)) {
            $errors[] = $this->trans('Contact form selectors contain invalid characters', [], 'Modules.Drsoftfrhcaptcha.Admin');
        }

        $rawSelectorsSubmit = (string)Tools::getValue('DRSOFT_FR_HCAPTCHA_SELECTORS_SUBMIT');
        if (!$this->isValidSelectors($rawSelectorsSubmit)) {
            $errors[] = $this->trans('Submit button selectors contain invalid characters', [], 'Modules.Drsoftfrhcaptcha.Admin');
        }

        // If any validation errors, stop here and don't save anything
        if (!empty($errors)) {
            return $this->displayError(implode('<br>', $errors))
                . $this->displayWarning(
                    $this->trans('Configuration not saved. Please fix the errors above.', [], 'Modules.Drsoftfrhcaptcha.Admin')
                );
        }

        // All valid, now save everything
        Configuration::updateValue('DRSOFT_FR_HCAPTCHA_SITE_KEY', $siteKey);

        // Only update secret key if it has changed (not placeholder)
        $isPlaceholder = ($secretKey === '••••••••••••••••••••');
        if (!$isPlaceholder) {
            $storedSecretKey = Configuration::get('DRSOFT_FR_HCAPTCHA_SECRET_KEY');
            $decryptedStoredKey = $this->decryptSecretKey($storedSecretKey);

            // Only encrypt and save if the key has actually changed
            if ($secretKey !== $decryptedStoredKey) {
                Configuration::updateValue('DRSOFT_FR_HCAPTCHA_SECRET_KEY', $this->encryptSecretKey($secretKey));
            }
        }
        Configuration::updateValue('DRSOFT_FR_HCAPTCHA_THEME', $theme);
        Configuration::updateValue('DRSOFT_FR_HCAPTCHA_SIZE', $size);
        Configuration::updateValue('DRSOFT_FR_HCAPTCHA_CONTACT_ENABLED', (int)Tools::getValue('DRSOFT_FR_HCAPTCHA_CONTACT_ENABLED'));
        Configuration::updateValue('DRSOFT_FR_HCAPTCHA_REGISTER_ENABLED', (int)Tools::getValue('DRSOFT_FR_HCAPTCHA_REGISTER_ENABLED'));
        Configuration::updateValue('DRSOFT_FR_HCAPTCHA_LOGIN_ENABLED', (int)Tools::getValue('DRSOFT_FR_HCAPTCHA_LOGIN_ENABLED'));
        Configuration::updateValue('DRSOFT_FR_HCAPTCHA_NEWSLETTER_ENABLED', (int)Tools::getValue('DRSOFT_FR_HCAPTCHA_NEWSLETTER_ENABLED'));
        Configuration::updateValue('DRSOFT_FR_HCAPTCHA_SELECTORS_LOGIN', $this->sanitizeSelectors($rawSelectorsLogin) ?: self::DEFAULT_SELECTORS_LOGIN);
        Configuration::updateValue('DRSOFT_FR_HCAPTCHA_SELECTORS_CONTACT', $this->sanitizeSelectors($rawSelectorsContact) ?: self::DEFAULT_SELECTORS_CONTACT);
        Configuration::updateValue('DRSOFT_FR_HCAPTCHA_SELECTORS_SUBMIT', $this->sanitizeSelectors($rawSelectorsSubmit) ?: self::DEFAULT_SELECTORS_SUBMIT);

        return $this->displayConfirmation(
            $this->trans('Configuration saved successfully', [], 'Modules.Drsoftfrhcaptcha.Admin')
        );
    }

    /**
     * Generate configuration form
     */
    private function displayConfigurationForm(): string
    {
        // Main configuration form
        $mainForm = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('hCaptcha Configuration', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Site Key', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_SITE_KEY',
                        'desc' => $this->trans(
                            'Get your keys at https://www.hcaptcha.com/',
                            [],
                            'Modules.Drsoftfrhcaptcha.Admin'
                        ),
                        'required' => true,
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Secret Key', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_SECRET_KEY',
                        'desc' => $this->trans(
                            'Never share this key publicly',
                            [],
                            'Modules.Drsoftfrhcaptcha.Admin'
                        ),
                        'required' => true,
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Theme', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_THEME',
                        'options' => [
                            'query' => [
                                ['id' => 'light', 'name' => $this->trans('Light', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                                ['id' => 'dark', 'name' => $this->trans('Dark', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Size', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_SIZE',
                        'options' => [
                            'query' => [
                                ['id' => 'normal', 'name' => $this->trans('Normal', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                                ['id' => 'compact', 'name' => $this->trans('Compact', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Enable on contact form', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_CONTACT_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Enable on registration form', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_REGISTER_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Enable on login form', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_LOGIN_ENABLED',
                        'is_bool' => true,
                        'desc' => $this->trans(
                            'Optional - May affect user experience',
                            [],
                            'Modules.Drsoftfrhcaptcha.Admin'
                        ),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Enable on newsletter registration', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_NEWSLETTER_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.Drsoftfrhcaptcha.Admin')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        // Advanced settings form
        $advancedForm = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Advanced Settings - CSS Selectors', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                    'icon' => 'icon-code',
                ],
                'description' => $this->trans(
                    'Customize CSS selectors if your theme uses non-standard form structures. Separate multiple selectors with commas. Leave empty to use default values.',
                    [],
                    'Modules.Drsoftfrhcaptcha.Admin'
                ),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Login form selectors', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_SELECTORS_LOGIN',
                        'desc' => $this->trans('Default:', [], 'Modules.Drsoftfrhcaptcha.Admin') . ' ' . self::DEFAULT_SELECTORS_LOGIN,
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Contact form selectors', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_SELECTORS_CONTACT',
                        'desc' => $this->trans('Default:', [], 'Modules.Drsoftfrhcaptcha.Admin') . ' ' . self::DEFAULT_SELECTORS_CONTACT,
                        'class' => 'fixed-width-xxl',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Submit button selectors', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                        'name' => 'DRSOFT_FR_HCAPTCHA_SELECTORS_SUBMIT',
                        'desc' => $this->trans('Default:', [], 'Modules.Drsoftfrhcaptcha.Admin') . ' ' . self::DEFAULT_SELECTORS_SUBMIT,
                        'class' => 'fixed-width-xxl',
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Modules.Drsoftfrhcaptcha.Admin'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        // Current values (use POST values if available to preserve user input after validation errors)
        $isSubmitted = Tools::isSubmit('submitdrsoftfrhcaptcha');

        // Get secret key - never display the real key, always use placeholder
        $displaySecretKey = '';
        if ($isSubmitted) {
            // Preserve user input after validation errors (new key typed by admin)
            $displaySecretKey = (string)Tools::getValue('DRSOFT_FR_HCAPTCHA_SECRET_KEY');
        } else {
            $storedSecretKey = Configuration::get('DRSOFT_FR_HCAPTCHA_SECRET_KEY');
            if (!empty($storedSecretKey)) {
                // Never decrypt and display the stored key
                $displaySecretKey = '••••••••••••••••••••';
            }
        }

        $helper->tpl_vars = [
            'fields_value' => [
                'DRSOFT_FR_HCAPTCHA_SITE_KEY' => $this->getFormValue('DRSOFT_FR_HCAPTCHA_SITE_KEY'),
                'DRSOFT_FR_HCAPTCHA_SECRET_KEY' => $displaySecretKey,
                'DRSOFT_FR_HCAPTCHA_THEME' => $this->getFormValue('DRSOFT_FR_HCAPTCHA_THEME'),
                'DRSOFT_FR_HCAPTCHA_SIZE' => $this->getFormValue('DRSOFT_FR_HCAPTCHA_SIZE'),
                'DRSOFT_FR_HCAPTCHA_CONTACT_ENABLED' => $this->getFormValue('DRSOFT_FR_HCAPTCHA_CONTACT_ENABLED'),
                'DRSOFT_FR_HCAPTCHA_REGISTER_ENABLED' => $this->getFormValue('DRSOFT_FR_HCAPTCHA_REGISTER_ENABLED'),
                'DRSOFT_FR_HCAPTCHA_LOGIN_ENABLED' => $this->getFormValue('DRSOFT_FR_HCAPTCHA_LOGIN_ENABLED'),
                'DRSOFT_FR_HCAPTCHA_NEWSLETTER_ENABLED' => $this->getFormValue('DRSOFT_FR_HCAPTCHA_NEWSLETTER_ENABLED'),
                'DRSOFT_FR_HCAPTCHA_SELECTORS_LOGIN' => $this->getFormValue('DRSOFT_FR_HCAPTCHA_SELECTORS_LOGIN', self::DEFAULT_SELECTORS_LOGIN),
                'DRSOFT_FR_HCAPTCHA_SELECTORS_CONTACT' => $this->getFormValue('DRSOFT_FR_HCAPTCHA_SELECTORS_CONTACT', self::DEFAULT_SELECTORS_CONTACT),
                'DRSOFT_FR_HCAPTCHA_SELECTORS_SUBMIT' => $this->getFormValue('DRSOFT_FR_HCAPTCHA_SELECTORS_SUBMIT', self::DEFAULT_SELECTORS_SUBMIT),
            ],
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$mainForm, $advancedForm]);
    }

    /**
     * Hook Header - Load hCaptcha script
     */
    public function hookDisplayHeader(): void
    {
        // Load custom CSS
        $this->context->controller->registerStylesheet(
            'modules-' . $this->name . '-style',
            'modules/' . $this->name . '/views/css/hcaptcha.css',
            ['media' => 'all', 'priority' => 150]
        );

        // Load hCaptcha script only if configured
        $siteKey = Configuration::get('DRSOFT_FR_HCAPTCHA_SITE_KEY');
        if (empty($siteKey)) {
            return;
        }

        // Build list of forms to auto-inject (dynamic injection via JS)
        $enabledForms = [];
        if ($this->context->controller instanceof AuthController
            && Configuration::get('DRSOFT_FR_HCAPTCHA_LOGIN_ENABLED')
        ) {
            $enabledForms[] = 'login';
        }
        if ($this->context->controller instanceof ContactController
            && Configuration::get('DRSOFT_FR_HCAPTCHA_CONTACT_ENABLED')
        ) {
            $enabledForms[] = 'contact';
        }

        // Global JavaScript configuration for hCaptcha
        $hcaptchaConfig = [
            'siteKey' => $siteKey,
            'theme' => Configuration::get('DRSOFT_FR_HCAPTCHA_THEME'),
            'size' => Configuration::get('DRSOFT_FR_HCAPTCHA_SIZE'),
            'selectors' => [
                'login' => $this->parseSelectors(Configuration::get('DRSOFT_FR_HCAPTCHA_SELECTORS_LOGIN') ?: self::DEFAULT_SELECTORS_LOGIN),
                'contact' => $this->parseSelectors(Configuration::get('DRSOFT_FR_HCAPTCHA_SELECTORS_CONTACT') ?: self::DEFAULT_SELECTORS_CONTACT),
                'submit' => $this->parseSelectors(Configuration::get('DRSOFT_FR_HCAPTCHA_SELECTORS_SUBMIT') ?: self::DEFAULT_SELECTORS_SUBMIT),
            ],
            'enabledForms' => $enabledForms,
            'i18n' => [
                'pleaseComplete' => $this->trans(
                    'Please complete the captcha before submitting the form.',
                    [],
                    'Modules.Drsoftfrhcaptcha.Shop'
                ),
                'errorPrefix' => $this->trans(
                    '[hCaptcha] Error:',
                    [],
                    'Modules.Drsoftfrhcaptcha.Shop'
                ),
            ],
        ];

        Media::addJsDef([
            'drsoftHcaptchaConfig' => $hcaptchaConfig
        ]);

        // Load hCaptcha API with onload callback for better initialization
        $this->context->controller->registerJavascript(
            'modules-' . $this->name . '-hcaptcha-api',
            'https://js.hcaptcha.com/1/api.js?onload=hcaptchaOnLoad&render=explicit',
            [
                'position' => 'bottom',
                'priority' => 150,
                'attributes' => 'async defer',
                'server' => 'remote'
            ]
        );

        // Load core hcaptcha.js script (handles all form injections)
        $this->context->controller->registerJavascript(
            'modules-' . $this->name . '-core',
            'modules/' . $this->name . '/views/js/hcaptcha.js',
            ['position' => 'bottom', 'priority' => 160]
        );
    }

    /**
     * Hook to display hCaptcha widget on registration form
     */
    public function hookDisplayCustomerAccountForm(): string
    {
        if (!Configuration::get('DRSOFT_FR_HCAPTCHA_REGISTER_ENABLED')) {
            return '';
        }

        return $this->displayHCaptchaWidget();
    }

    /**
     * Hook before account creation
     */
    public function hookActionSubmitAccountBefore($params): bool
    {
        if (!Configuration::get('DRSOFT_FR_HCAPTCHA_REGISTER_ENABLED')) {
            return true;
        }

        return $this->validateHCaptcha();
    }

    /**
     * Hook before authentication
     */
    public function hookActionAuthenticationSubmitHCaptcha($params): bool
    {
        if (!Configuration::get('DRSOFT_FR_HCAPTCHA_LOGIN_ENABLED')) {
            return true;
        }

        return $this->validateHCaptcha();
    }

    /**
     * Hook before contact
     */
    public function hookActionContactSubmitHCaptcha($params): bool
    {
        if (!Configuration::get('DRSOFT_FR_HCAPTCHA_CONTACT_ENABLED')) {
            return true;
        }

        return $this->validateHCaptcha();
    }

    /**
     * Hook to display hCaptcha widget on newsletter registration form
     */
    public function hookDisplayNewsletterRegistration(array $params): string
    {
        if (!Configuration::get('DRSOFT_FR_HCAPTCHA_NEWSLETTER_ENABLED')) {
            return '';
        }

        return $this->displayHCaptchaWidget();
    }

    /**
     * Hook before newsletter registration
     */
    public function hookActionNewsletterRegistrationBefore(array $params): bool
    {
        if (!Configuration::get('DRSOFT_FR_HCAPTCHA_NEWSLETTER_ENABLED')) {
            return true;
        }

        $result = $this->validateHCaptcha();

        if (false === $result) {
            $params['hookError'] = $this->trans(
                'Captcha verification failed. Please try again.',
                [],
                'Modules.Drsoftfrhcaptcha.Shop'
            );

            return false;
        }


        return true;
    }

    /**
     * Display hCaptcha widget
     */
    private function displayHCaptchaWidget(): string
    {
        $siteKey = Configuration::get('DRSOFT_FR_HCAPTCHA_SITE_KEY');

        if (empty($siteKey)) {
            return '';
        }

        $this->context->smarty->assign([
            'siteKey' => $siteKey,
            'theme' => Configuration::get('DRSOFT_FR_HCAPTCHA_THEME'),
            'size' => Configuration::get('DRSOFT_FR_HCAPTCHA_SIZE'),
        ]);

        return (string)$this->display(__FILE__, 'views/templates/hook/hcaptcha.tpl');
    }

    /**
     * Validate hCaptcha
     */
    private function validateHCaptcha(): bool
    {
        $response = Tools::getValue('h-captcha-response');

        if (empty($response)) {
            $this->context->controller->errors[] = $this->trans(
                'Please complete the captcha',
                [],
                'Modules.Drsoftfrhcaptcha.Shop'
            );

            return false;
        }

        if (false === $this->verifyHCaptcha($response)) {
            $this->context->controller->errors[] = $this->trans(
                'Captcha verification failed. Please try again.',
                [],
                'Modules.Drsoftfrhcaptcha.Shop'
            );

            return false;
        }

        return true;
    }

    /**
     * Server-side hCaptcha verification
     */
    private function verifyHCaptcha($response): bool
    {
        $encryptedSecretKey = Configuration::get('DRSOFT_FR_HCAPTCHA_SECRET_KEY');

        if (empty($encryptedSecretKey)) {
            PrestaShopLogger::addLog(
                'hCaptcha: Secret key not configured',
                3,
                null,
                'DrsoftfrHcaptcha'
            );

            return false;
        }

        // Decrypt the secret key
        $secretKey = $this->decryptSecretKey($encryptedSecretKey);

        $data = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => Tools::getRemoteAddr(),
        ];

        $verifyResponse = null;

        // Try cURL first (preferred method)
        if (function_exists('curl_init') && function_exists('curl_exec')) {
            $verifyResponse = $this->verifyWithCurl($data);
        }

        // Fallback to file_get_contents if cURL failed or unavailable
        if ($verifyResponse === null && ini_get('allow_url_fopen')) {
            $verifyResponse = $this->verifyWithFileGetContents($data);
        }

        // If both methods failed
        if ($verifyResponse === null) {
            PrestaShopLogger::addLog(
                'hCaptcha: No available method to verify captcha (cURL and file_get_contents both unavailable)',
                3,
                null,
                'DrsoftfrHcaptcha'
            );

            return false;
        }

        // Check if json_decode is available
        if (!function_exists('json_decode')) {
            PrestaShopLogger::addLog(
                'hCaptcha: json_decode function is not available (JSON extension required)',
                3,
                null,
                'DrsoftfrHcaptcha'
            );

            return false;
        }

        $responseData = json_decode($verifyResponse);

        // Check for JSON decode errors
        if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
            PrestaShopLogger::addLog(
                'hCaptcha: JSON decode error - ' . json_last_error_msg(),
                3,
                null,
                'DrsoftfrHcaptcha'
            );

            return false;
        }

        if (!$responseData) {
            PrestaShopLogger::addLog(
                'hCaptcha: Invalid API response (empty or malformed JSON)',
                3,
                null,
                'DrsoftfrHcaptcha'
            );

            return false;
        }

        // Log for debug
        if (isset($responseData->success) && !$responseData->success) {
            $errorCodes = isset($responseData->{'error-codes'}) ? implode(', ', $responseData->{'error-codes'}) : 'unknown';
            PrestaShopLogger::addLog(
                'hCaptcha: Validation failed - ' . $errorCodes,
                2,
                null,
                'DrsoftfrHcaptcha'
            );
        }

        return isset($responseData->success) && $responseData->success === true;
    }

    /**
     * Verify hCaptcha using cURL
     *
     * @param array $data Verification data
     * @return string|null API response or null on error
     */
    private function verifyWithCurl(array $data): ?string
    {
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, 'https://hcaptcha.com/siteverify');
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($verify, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($verify, CURLOPT_TIMEOUT, 10);
        curl_setopt($verify, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($verify, CURLOPT_MAXREDIRS, 3);
        curl_setopt($verify, CURLOPT_FOLLOWLOCATION, true);

        $verifyResponse = curl_exec($verify);

        if (curl_errno($verify)) {
            $error = curl_error($verify);
            curl_close($verify);
            PrestaShopLogger::addLog(
                'hCaptcha: cURL error - ' . $error,
                3,
                null,
                'DrsoftfrHcaptcha'
            );

            return null;
        }

        curl_close($verify);

        return $verifyResponse !== false ? $verifyResponse : null;
    }

    /**
     * Verify hCaptcha using file_get_contents (fallback method)
     *
     * @param array $data Verification data
     * @return string|null API response or null on error
     */
    private function verifyWithFileGetContents(array $data): ?string
    {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($data),
                'timeout' => 10,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ];

        $context = stream_context_create($options);
        $verifyResponse = @file_get_contents('https://hcaptcha.com/siteverify', false, $context);

        if ($verifyResponse === false) {
            PrestaShopLogger::addLog(
                'hCaptcha: file_get_contents error - Unable to verify captcha',
                3,
                null,
                'DrsoftfrHcaptcha'
            );

            return null;
        }

        return $verifyResponse;
    }

    /**
     * Validate hCaptcha key format
     *
     * @param string $key The key to validate
     * @param string $type Key type ('site' or 'secret')
     * @return bool True if valid, false otherwise
     */
    private function isValidHCaptchaKey(string $key, string $type): bool
    {
        // Check if empty
        if (empty($key)) {
            return false;
        }

        // Check length
        $keyLength = strlen($key);
        if ($keyLength < self::HCAPTCHA_KEY_MIN_LENGTH || $keyLength > self::HCAPTCHA_KEY_MAX_LENGTH) {
            return false;
        }

        // Check for valid characters: alphanumeric, hyphens, underscores
        // hCaptcha keys can contain: a-z, A-Z, 0-9, - and _
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
            return false;
        }

        // Additional security checks
        // Reject keys that look suspicious (SQL injection attempts, XSS, etc.)
        $dangerous = ['<', '>', '"', "'", ';', '(', ')', '{', '}', '[', ']', '\\', '/', '..'];
        foreach ($dangerous as $char) {
            if (strpos($key, $char) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Encrypt the secret key for storage
     *
     * @param string $secretKey Plain secret key
     * @return string Encrypted secret key or plain if encryption fails
     */
    private function encryptSecretKey(string $secretKey): string
    {
        // Check if the key is already encrypted
        if (strpos($secretKey, 'ENC:') === 0) {
            return $secretKey;
        }

        // Use OpenSSL if available
        if (function_exists('openssl_encrypt')) {
            $encryptionKey = $this->getEncryptionKey();
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
            $encrypted = openssl_encrypt($secretKey, 'aes-256-cbc', $encryptionKey, 0, $iv);

            if ($encrypted !== false) {
                // Store IV with encrypted data
                return 'ENC:' . base64_encode($iv . $encrypted);
            }
        }

        // Fallback: store as plain text with a warning
        PrestaShopLogger::addLog(
            'hCaptcha: OpenSSL not available, secret key stored in plain text (security risk)',
            2,
            null,
            'DrsoftfrHcaptcha'
        );

        return $secretKey;
    }

    /**
     * Decrypt the secret key from storage
     *
     * @param string $encryptedKey Encrypted secret key
     * @return string Plain secret key
     */
    private function decryptSecretKey(string $encryptedKey): string
    {
        // Check if the key is encrypted
        if (strpos($encryptedKey, 'ENC:') !== 0) {
            return $encryptedKey;
        }

        // Remove prefix and decode
        $data = base64_decode(substr($encryptedKey, 4));

        if ($data === false) {
            PrestaShopLogger::addLog(
                'hCaptcha: Failed to decode encrypted secret key',
                3,
                null,
                'DrsoftfrHcaptcha'
            );
            return '';
        }

        // Use OpenSSL if available
        if (function_exists('openssl_decrypt')) {
            $encryptionKey = $this->getEncryptionKey();
            $ivLength = openssl_cipher_iv_length('aes-256-cbc');
            $iv = substr($data, 0, $ivLength);
            $encrypted = substr($data, $ivLength);

            $decrypted = openssl_decrypt($encrypted, 'aes-256-cbc', $encryptionKey, 0, $iv);

            if ($decrypted !== false) {
                return $decrypted;
            }

            PrestaShopLogger::addLog(
                'hCaptcha: Failed to decrypt secret key',
                3,
                null,
                'DrsoftfrHcaptcha'
            );
        }

        return '';
    }

    /**
     * Get encryption key based on PrestaShop configuration
     *
     * @return string Encryption key
     */
    private function getEncryptionKey(): string
    {
        // Use PrestaShop's cookie key for encryption (unique per installation)
        // _COOKIE_IV_ may not exist in older PrestaShop versions
        $cookieIv = defined('_COOKIE_IV_') ? _COOKIE_IV_ : '';

        return hash('sha256', _COOKIE_KEY_ . $cookieIv . $this->name);
    }

    /**
     * Get form value with fallback to configuration
     * Helper method to avoid code duplication in displayConfigurationForm
     *
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Form value or configuration value
     */
    private function getFormValue(string $key, $default = '')
    {
        $isSubmitted = Tools::isSubmit('submitdrsoftfrhcaptcha');

        if ($isSubmitted) {
            return Tools::getValue($key);
        }

        $configValue = Configuration::get($key);
        return $configValue !== false ? $configValue : $default;
    }

    /**
     * Check if CSS selectors contain only valid characters
     *
     * @param string $selectors Raw selectors input
     * @return bool True if valid or empty, false if contains invalid characters
     */
    private function isValidSelectors(string $selectors): bool
    {
        // Remove HTML/JS tags first
        $selectors = strip_tags($selectors);
        $selectors = trim($selectors);

        // Empty is considered valid (will use default)
        if (empty($selectors)) {
            return true;
        }

        // Check length to prevent DoS
        if (strlen($selectors) > 1000) {
            return false;
        }

        // Reject dangerous patterns that could be used for injection
        $dangerousPatterns = [
            '/javascript:/i',           // JavaScript protocol
            '/data:/i',                 // Data protocol
            '/<script/i',               // Script tags
            '/expression\s*\(/i',       // CSS expressions (IE)
            '/import\s*["\']/',         // CSS @import
            '/@import/i',               // CSS @import
            '/url\s*\(/i',              // CSS url() - could load external resources
            '/behavior\s*:/i',          // IE behavior
            '/binding\s*:/i',           // XBL binding
            '/vbscript:/i',             // VBScript protocol
            '/onclick/i',               // Event handlers
            '/onerror/i',               // Event handlers
            '/onload/i',                // Event handlers
            '/\{\{/',                   // Template injection
            '/\$\{/',                   // Template injection
            '/<!--/',                   // HTML comments
            '/-->/',                    // HTML comments
            '/\/\*/',                   // CSS comments (could hide malicious code)
            '/\*\//',                   // CSS comments
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $selectors)) {
                return false;
            }
        }

        // Allowed characters for CSS selectors:
        // - Letters (unicode), numbers, whitespace
        // - # (ID), . (class), - _ (naming)
        // - [ ] = * ^ $ | (attribute selectors)
        // - : (pseudo-classes/elements)
        // - " ' (attribute values)
        // - ( ) (functional pseudo-classes)
        // - , (selector separator)
        // - > + ~ (combinators)
        if (!preg_match('/^[\w\s\#\.\-\[\]\=\*\^\$\|\:\"\'\(\)\,\>\+\~]+$/u', $selectors)) {
            return false;
        }

        // Additional validation: Check for balanced quotes and brackets
        if (!$this->areQuotesBalanced($selectors)) {
            return false;
        }

        if (!$this->areBracketsBalanced($selectors)) {
            return false;
        }

        return true;
    }

    /**
     * Check if quotes are balanced in selectors
     *
     * @param string $selectors CSS selectors string
     * @return bool True if balanced, false otherwise
     */
    private function areQuotesBalanced(string $selectors): bool
    {
        $singleQuotes = substr_count($selectors, "'");
        $doubleQuotes = substr_count($selectors, '"');

        // Quotes must be in pairs
        return ($singleQuotes % 2 === 0) && ($doubleQuotes % 2 === 0);
    }

    /**
     * Check if brackets are balanced in selectors
     *
     * @param string $selectors CSS selectors string
     * @return bool True if balanced, false otherwise
     */
    private function areBracketsBalanced(string $selectors): bool
    {
        $stack = [];
        $pairs = [
            '(' => ')',
            '[' => ']',
        ];

        for ($i = 0; $i < strlen($selectors); $i++) {
            $char = $selectors[$i];

            if (isset($pairs[$char])) {
                // Opening bracket
                $stack[] = $char;
            } elseif (in_array($char, $pairs)) {
                // Closing bracket
                if (empty($stack)) {
                    return false;
                }

                $last = array_pop($stack);
                if ($pairs[$last] !== $char) {
                    return false;
                }
            }
        }

        // Stack should be empty if all brackets are balanced
        return empty($stack);
    }

    /**
     * Sanitize CSS selectors input
     *
     * @param string $selectors Raw selectors input from form
     * @return string|null Sanitized selectors or null if empty
     */
    private function sanitizeSelectors(string $selectors): ?string
    {
        // Remove HTML/JS tags
        $selectors = strip_tags($selectors);

        // Trim whitespace
        $selectors = trim($selectors);

        // Return null if empty (will use default)
        if (empty($selectors)) {
            return null;
        }

        // Clean up spaces around commas for consistency
        $selectors = preg_replace('/\s*,\s*/', ', ', $selectors);

        // Clean up multiple spaces
        $selectors = preg_replace('/\s+/', ' ', $selectors);

        return $selectors;
    }

    /**
     * Parse selectors string into array
     *
     * @param string $selectors Comma-separated CSS selectors
     * @return array Array of trimmed selectors
     */
    private function parseSelectors(string $selectors): array
    {
        $parsed = array_map('trim', explode(',', $selectors));

        return array_filter($parsed, function ($selector) {
            return !empty($selector);
        });
    }
}
