<?php

global $_MODULE;
$_MODULE = [];

// Traductions pour le back-office (Admin)
$_MODULE['<{drsoftfrhcaptcha}prestashop>drsoftfrhcaptcha'] = 'Protection hCaptcha';
$_MODULE['<{drsoftfrhcaptcha}prestashop>configure'] = 'Configuration hCaptcha';

// Messages de configuration
$_MODULE['Modules.Drsoftfrhcaptcha.Admin'] = [
    'drSoft.fr - hCaptcha Protection' => 'drSoft.fr - Protection hCaptcha',
    'Protect your forms against bots with hCaptcha (GDPR compliant)' => 'Protégez vos formulaires contre les bots avec hCaptcha (conforme RGPD)',
    'Are you sure you want to uninstall this module?' => 'Êtes-vous sûr de vouloir désinstaller ce module ?',
    'hCaptcha keys are required' => 'Les clés hCaptcha sont obligatoires',
    'Configuration saved successfully' => 'Configuration enregistrée avec succès',
    'hCaptcha Configuration' => 'Configuration hCaptcha',
    'Site Key' => 'Clé du site (Site Key)',
    'Get your keys at https://www.hcaptcha.com/' => 'Obtenez vos clés sur https://www.hcaptcha.com/',
    'Secret Key' => 'Clé secrète (Secret Key)',
    'Never share this key publicly' => 'Ne partagez jamais cette clé publiquement',
    'Theme' => 'Thème',
    'Light' => 'Clair',
    'Dark' => 'Sombre',
    'Size' => 'Taille',
    'Normal' => 'Normal',
    'Compact' => 'Compact',
    'Enable on contact form' => 'Activer sur le formulaire de contact',
    'Enable on registration form' => 'Activer sur le formulaire d\'inscription',
    'Enable on login form' => 'Activer sur le formulaire de connexion',
    'Optional - May affect user experience' => 'Optionnel - Peut gêner l\'expérience utilisateur',
    'Enable on newsletter registration' => 'Activer sur l\'inscription à la newsletter',
    'Yes' => 'Oui',
    'No' => 'Non',
    'Save' => 'Enregistrer',
    'Advanced Settings - CSS Selectors' => 'Paramètres avancés - Sélecteurs CSS',
    'Customize CSS selectors if your theme uses non-standard form structures. Separate multiple selectors with commas. Leave empty to use default values.' => 'Personnalisez les sélecteurs CSS si votre thème utilise des structures de formulaires non standard. Séparez les sélecteurs multiples par des virgules. Laissez vide pour utiliser les valeurs par défaut.',
    'Login form selectors' => 'Sélecteurs du formulaire de connexion',
    'Contact form selectors' => 'Sélecteurs du formulaire de contact',
    'Submit button selectors' => 'Sélecteurs du bouton de soumission',
    'Default:' => 'Par défaut :',
    'Login form selectors contain invalid characters' => 'Les sélecteurs du formulaire de connexion contiennent des caractères invalides',
    'Contact form selectors contain invalid characters' => 'Les sélecteurs du formulaire de contact contiennent des caractères invalides',
    'Submit button selectors contain invalid characters' => 'Les sélecteurs du bouton de soumission contiennent des caractères invalides',
    'Configuration not saved. Please fix the errors above.' => 'Configuration non enregistrée. Veuillez corriger les erreurs ci-dessus.',
    'Site key format is invalid' => 'Le format de la clé du site est invalide',
    'Secret key format is invalid' => 'Le format de la clé secrète est invalide',
    'Warning: OpenSSL extension is not available. The secret key will be stored in plain text in the database, which is a security risk. Please enable the OpenSSL PHP extension.' => 'Attention : L\'extension OpenSSL n\'est pas disponible. La clé secrète sera stockée en clair dans la base de données, ce qui représente un risque de sécurité. Veuillez activer l\'extension PHP OpenSSL.',
];

// Messages pour le front-office (Shop)
$_MODULE['Modules.Drsoftfrhcaptcha.Shop'] = [
    'Please complete the captcha' => 'Veuillez compléter le captcha',
    'Captcha verification failed. Please try again.' => 'La vérification du captcha a échoué. Veuillez réessayer.',
    'Please complete the captcha before submitting the form.' => 'Veuillez compléter le captcha avant de soumettre le formulaire.',
    '[hCaptcha] Error:' => '[hCaptcha] Erreur :',
];
