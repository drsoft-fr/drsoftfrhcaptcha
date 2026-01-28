<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class AuthController extends AuthControllerCore
{
    public function postProcess(): void
    {
        if (
            Tools::isSubmit('submitLogin')
            && Module::isInstalled('drsoftfrhcaptcha')
            && Module::isEnabled('drsoftfrhcaptcha')
            && false === Module::getInstanceByName('drsoftfrhcaptcha')->hookActionAuthenticationSubmitHCaptcha([])
        ) {
            $link = $this
                ->context
                ->link
                ->getPageLink('authentication');

            $this->redirectWithNotifications($link);

            return;
        }

        parent::postProcess();
    }
}
