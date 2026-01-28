# drSoft.fr - hCaptcha Protection

[![PrestaShop](https://img.shields.io/badge/PrestaShop-1.7%20%7C%208.x-blue.svg)](https://www.prestashop.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-8892BF.svg)](https://php.net/)

Protect your PrestaShop store forms against bots and spam with [hCaptcha](https://www.hcaptcha.com/) - a privacy-focused, GDPR-compliant alternative to reCAPTCHA.

## Features

- **Multi-form protection**: Secure contact, registration, login, and newsletter forms
- **GDPR compliant**: hCaptcha respects user privacy and is fully GDPR compliant
- **Easy configuration**: Simple back-office interface with no coding required
- **Customizable appearance**: Light/dark themes and normal/compact sizes
- **Advanced CSS selectors**: Support for custom themes with non-standard form structures
- **Secure storage**: Secret keys are encrypted using AES-256-CBC
- **Multi-language**: Available in English and French

## Requirements

- PrestaShop 1.7.0 - 8.x
- PHP 7.4 or higher
- OpenSSL PHP extension (recommended for encrypted key storage)
- cURL or `allow_url_fopen` enabled

## Installation

### Manual Installation

1. Download the latest release from [GitHub Releases](https://github.com/drsoft-fr/drsoftfrhcaptcha/releases)
2. Extract the `drsoftfrhcaptcha` folder
3. Upload it to your PrestaShop `/modules/` directory
4. Go to your PrestaShop back-office → Modules → Module Manager
5. Search for "hCaptcha" and click "Install"

### From GitHub

```bash
cd /path/to/prestashop/modules/
git clone git@github.com:drsoft-fr/drsoftfrhcaptcha.git
```

Then install via the PrestaShop back-office.

## Configuration

### 1. Get your hCaptcha keys

1. Create a free account at [hCaptcha.com](https://www.hcaptcha.com/)
2. Add your website and get your **Site Key** and **Secret Key**

### 2. Configure the module

1. Go to **Modules → Module Manager**
2. Find "drSoft.fr - hCaptcha Protection" and click **Configure**
3. Enter your **Site Key** and **Secret Key**
4. Choose your preferred theme (Light/Dark) and size (Normal/Compact)
5. Enable protection on the forms you want to secure:
   - Contact form
   - Registration form
   - Login form (optional)
   - Newsletter registration (optional)
6. Click **Save**

### Advanced Settings

If your theme uses custom form structures, you can specify custom CSS selectors:

- **Login form selectors**: CSS selectors to identify login forms
- **Contact form selectors**: CSS selectors to identify contact forms
- **Submit button selectors**: CSS selectors to identify submit buttons

Leave empty to use default selectors compatible with most themes.

## Compatibility

| PrestaShop Version | Status |
|-------------------|--------|
| 1.7.x | ✅ Compatible |
| 8.0.x | ✅ Compatible |
| 8.1.x | ✅ Compatible |
| 8.2.x | ✅ Compatible |
| 9.x | ⚠️ Partial (overrides deprecated) |

### Theme Compatibility

The module is compatible with:
- Classic theme (default)
- Most themes following PrestaShop standards
- Custom themes (using advanced CSS selectors)

## How It Works

1. The module hooks into PrestaShop's form display and submission events
2. hCaptcha widget is injected into enabled forms
3. On form submission, the captcha response is verified server-side with hCaptcha API
4. If verification fails, the form submission is blocked with an error message

## Security Features

- **Encrypted storage**: Secret keys are encrypted with AES-256-CBC before storage
- **Server-side verification**: All captcha responses are verified on the server
- **Input validation**: CSS selectors are validated against injection attacks
- **SSL verification**: API calls to hCaptcha use SSL with peer verification

## Troubleshooting

### hCaptcha widget not appearing

1. Check that your Site Key is correctly configured
2. Clear PrestaShop cache (Advanced Parameters → Performance)
3. Check browser console for JavaScript errors
4. Verify the form selectors match your theme's structure

### "Captcha verification failed" error

1. Verify your Secret Key is correct
2. Check that your server can reach `hcaptcha.com` (no firewall blocking)
3. Ensure cURL or `allow_url_fopen` is enabled in PHP

### Module conflicts

If you experience conflicts with other modules:
1. Try disabling other captcha/security modules
2. Check for JavaScript errors in browser console
3. Verify override files are properly loaded

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Support

- **Issues**: [GitHub Issues](https://github.com/drsoft-fr/drsoftfrhcaptcha/issues)
- **Email**: contact@drsoft.fr

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Author

**Dylan Ramos** - [drSoft.fr](https://www.drsoft.fr)

---

Made with ❤️ by [drSoft.fr](https://www.drsoft.fr)
