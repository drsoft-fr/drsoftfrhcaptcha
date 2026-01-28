<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class ContactformOverride extends Contactform
{
    public function sendMessage(): void
    {
        $hcaptchaValid = true;

        if (Module::isInstalled('drsoftfrhcaptcha') && Module::isEnabled('drsoftfrhcaptcha')) {
            $hcaptchaValid = Module::getInstanceByName('drsoftfrhcaptcha')->hookActionContactSubmitHCaptcha([]);
        }

        if (true === $hcaptchaValid) {
            parent::sendMessage();
        }
    }
}
