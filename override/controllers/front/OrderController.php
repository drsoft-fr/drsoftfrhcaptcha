<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderController extends OrderControllerCore
{
    public function postProcess(): void
    {
        if (
            Tools::isSubmit('submitCreate')
            && Module::isInstalled('drsoftfrhcaptcha')
            && Module::isEnabled('drsoftfrhcaptcha')
            && false === Module::getInstanceByName('drsoftfrhcaptcha')->hookActionSubmitAccountBefore([])
            && !empty($this->errors)
        ) {
            unset($_POST['submitCreate']);
        }

        parent::postProcess();
    }
}
