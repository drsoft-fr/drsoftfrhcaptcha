{**
 * hCaptcha Widget Template
 * Displays the hCaptcha protection widget on forms
 *
 * @author Dylan Ramos - drSoft.fr
 * @copyright 2026 drSoft.fr
 * @license MIT
 *}

<div class="form-group hcaptcha-container">
    <div class="h-captcha"
         data-sitekey="{$siteKey|escape:'htmlall':'UTF-8'}"
         data-theme="{$theme|escape:'htmlall':'UTF-8'}"
         data-size="{$size|escape:'htmlall':'UTF-8'}"
         data-callback="onHCaptchaSuccess"
         data-expired-callback="onHCaptchaExpired"
         data-error-callback="onHCaptchaError">
    </div>
</div>
