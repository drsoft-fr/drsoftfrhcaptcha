/**
 * hCaptcha Core Module - Reusable functions
 * Centralized hCaptcha widget management and client-side validation
 *
 * @author Dylan Ramos - drSoft.fr
 * @copyright 2026 drSoft.fr
 * @license MIT
 */

(function () {
  "use strict";

  // Global configuration passed from PHP
  var config =
    typeof drsoftHcaptchaConfig !== "undefined" ? drsoftHcaptchaConfig : {};

  /**
   * Global namespace for reusable functions
   */
  var DrsoftHCaptcha = {
    /**
     * Get configuration
     */
    getConfig: function () {
      return config;
    },

    /**
     * Find an element by testing multiple selectors
     */
    findElement: function (parent, selectors) {
      if (!parent || !selectors) {
        return null;
      }

      for (var i = 0; i < selectors.length; i++) {
        var element = parent.querySelector(selectors[i]);
        if (element) {
          return element;
        }
      }

      return null;
    },

    /**
     * Get selectors from config or use defaults
     */
    getSelectors: function (type) {
      var defaults = {
        login: [
          "#login-form",
          ".login-form form",
          'form[action*="login"]',
          'form[id*="login"]',
        ],
        contact: [
          ".contact-form form",
          "#contact-form",
          'form[action*="contact"]',
          'form[id*="contact"]',
          ".contact-rich form",
        ],
        submit: [
          'button[type="submit"]',
          'input[type="submit"]',
          ".btn-primary",
          ".form-control-submit",
        ],
      };

      if (
        config.selectors &&
        config.selectors[type] &&
        config.selectors[type].length > 0
      ) {
        return config.selectors[type];
      }

      return defaults[type] || [];
    },

    /**
     * Find the submit button of a form
     */
    findSubmitButton: function (form) {
      if (!form) {
        return null;
      }

      return this.findElement(form, this.getSelectors("submit"));
    },

    /**
     * Find the parent form of an element
     */
    findParentForm: function (element) {
      if (!element) {
        return null;
      }

      return element.closest("form");
    },

    /**
     * Check if an hCaptcha widget exists in an element
     */
    hasWidget: function (container) {
      if (!container) {
        return false;
      }

      return container.querySelector(".h-captcha") !== null;
    },

    /**
     * Display an error message in a form
     */
    showError: function (form, message) {
      if (!form) {
        return;
      }

      this.hideError(form);

      var errorDiv = document.createElement("div");
      errorDiv.className = "alert alert-danger hcaptcha-error";
      errorDiv.setAttribute("role", "alert");
      errorDiv.textContent = message;

      var hcaptchaContainer = form.querySelector(".hcaptcha-container");
      if (hcaptchaContainer) {
        hcaptchaContainer.parentNode.insertBefore(errorDiv, hcaptchaContainer);
      } else {
        form.insertBefore(errorDiv, form.firstChild);
      }
    },

    /**
     * Hide the error message of a form
     */
    hideError: function (form) {
      if (!form) {
        return;
      }

      var existingError = form.querySelector(".hcaptcha-error");
      if (existingError) {
        existingError.remove();
      }
    },

    /**
     * Create the hCaptcha widget HTML
     */
    createWidget: function (options) {
      options = options || {};

      var container = document.createElement("div");
      container.className = "form-group hcaptcha-container";

      var widget = document.createElement("div");
      widget.className = "h-captcha";
      widget.setAttribute("data-sitekey", options.siteKey || config.siteKey);
      widget.setAttribute(
        "data-theme",
        options.theme || config.theme || "light",
      );
      widget.setAttribute("data-size", options.size || config.size || "normal");
      widget.setAttribute(
        "data-callback",
        options.callback || "onHCaptchaSuccess",
      );
      widget.setAttribute(
        "data-expired-callback",
        options.expiredCallback || "onHCaptchaExpired",
      );
      widget.setAttribute(
        "data-error-callback",
        options.errorCallback || "onHCaptchaError",
      );

      container.appendChild(widget);

      return {
        container: container,
        widget: widget,
      };
    },

    /**
     * Inject an hCaptcha widget into a form
     */
    injectWidget: function (form, options) {
      if (!form || this.hasWidget(form)) {
        return null;
      }

      var submitBtn = this.findSubmitButton(form);
      if (!submitBtn) {
        return null;
      }

      var elements = this.createWidget(options);

      // Insert before the submit button (or its .form-group parent)
      var submitParent =
        submitBtn.closest(".form-group") || submitBtn.parentNode;
      submitParent.parentNode.insertBefore(elements.container, submitParent);

      // Mark as initialized immediately to prevent double processing
      elements.widget.dataset.hcaptchaInitialized = "true";

      // Render the widget if hCaptcha API is already loaded
      if (typeof hcaptcha !== "undefined") {
        elements.widget.dataset.hcaptchaRendered = "true";
        elements.widget.dataset.hcaptchaWidgetId = hcaptcha.render(
          elements.widget,
          {
            sitekey: options.siteKey || config.siteKey,
            theme: options.theme || config.theme || "light",
            size: options.size || config.size || "normal",
            callback: window[options.callback || "onHCaptchaSuccess"],
            "expired-callback":
              window[options.expiredCallback || "onHCaptchaExpired"],
            "error-callback":
              window[options.errorCallback || "onHCaptchaError"],
          },
        );
      }

      // Add validation to submit
      this.attachSubmitValidation(form);

      return elements;
    },

    /**
     * Attach validation to a form submit
     */
    attachSubmitValidation: function (form, errorMessage) {
      if (!form || form.dataset.hcaptchaValidation === "true") {
        return;
      }

      form.dataset.hcaptchaValidation = "true";
      var self = this;

      form.addEventListener("submit", function (e) {
        var response = form.querySelector('[name="h-captcha-response"]');

        if (!response || !response.value) {
          e.preventDefault();
          e.stopPropagation();
          var defaultMessage =
            config.i18n && config.i18n.pleaseComplete
              ? config.i18n.pleaseComplete
              : "Please complete the captcha before submitting the form.";
          self.showError(form, errorMessage || defaultMessage);
          return false;
        }

        self.hideError(form);

        // Reset hCaptcha widget after submission to allow resubmission on AJAX forms.
        // Deferred to next tick so the AJAX handler captures form data before reset.
        var widget = form.querySelector(".h-captcha");
        if (widget && typeof hcaptcha !== "undefined") {
          var widgetId = widget.dataset.hcaptchaWidgetId;
          setTimeout(function () {
            hcaptcha.reset(widgetId);
          }, 100);
        }
      });
    },

    /**
     * Initialize all hCaptcha widgets present on the page
     */
    initWidgets: function () {
      var self = this;
      var widgets = document.querySelectorAll(".h-captcha");

      if (widgets.length === 0) {
        return;
      }

      widgets.forEach(function (widget) {
        var form = self.findParentForm(widget);
        if (!form) {
          return;
        }

        // First-time setup (submit button config, callbacks, validation)
        if (widget.dataset.hcaptchaInitialized !== "true") {
          widget.dataset.hcaptchaInitialized = "true";

          // Add callbacks if not defined
          if (!widget.hasAttribute("data-callback")) {
            widget.setAttribute("data-callback", "onHCaptchaSuccess");
          }
          if (!widget.hasAttribute("data-expired-callback")) {
            widget.setAttribute("data-expired-callback", "onHCaptchaExpired");
          }
          if (!widget.hasAttribute("data-error-callback")) {
            widget.setAttribute("data-error-callback", "onHCaptchaError");
          }

          // Attach validation
          self.attachSubmitValidation(form);
        }

        // Render if API is available and not yet rendered
        // (separated from init to handle widgets created before API was loaded)
        if (
          typeof hcaptcha !== "undefined" &&
          widget.dataset.hcaptchaRendered !== "true"
        ) {
          widget.dataset.hcaptchaRendered = "true";
          widget.dataset.hcaptchaWidgetId = hcaptcha.render(widget, {
            sitekey: widget.getAttribute("data-sitekey") || config.siteKey,
            theme: widget.getAttribute("data-theme") || config.theme || "light",
            size: widget.getAttribute("data-size") || config.size || "normal",
            callback:
              window[
                widget.getAttribute("data-callback") || "onHCaptchaSuccess"
              ],
            "expired-callback":
              window[
                widget.getAttribute("data-expired-callback") ||
                  "onHCaptchaExpired"
              ],
            "error-callback":
              window[
                widget.getAttribute("data-error-callback") || "onHCaptchaError"
              ],
          });
        }
      });
    },

    /**
     * Observe DOM mutations to detect dynamically added widgets or forms
     */
    observeDynamicWidgets: function () {
      if (typeof MutationObserver === "undefined") {
        return;
      }

      var self = this;
      var observer = new MutationObserver(function (mutations) {
        var shouldInit = false;

        mutations.forEach(function (mutation) {
          if (mutation.addedNodes.length > 0) {
            mutation.addedNodes.forEach(function (node) {
              if (node.nodeType === Node.ELEMENT_NODE) {
                // Check for hCaptcha widgets
                if (
                  (node.querySelectorAll &&
                    node.querySelectorAll(".h-captcha").length > 0) ||
                  (node.classList && node.classList.contains("h-captcha"))
                ) {
                  shouldInit = true;
                }
                // Check for forms (for dynamic injection)
                if (
                  node.tagName === "FORM" ||
                  (node.querySelectorAll &&
                    node.querySelectorAll("form").length > 0)
                ) {
                  shouldInit = true;
                }
              }
            });
          }
        });

        if (shouldInit) {
          setTimeout(function () {
            self.initWidgets();
            self.autoInjectForms();
          }, 100);
        }
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true,
      });
    },

    /**
     * Inject hCaptcha widget into a specific form type
     *
     * @param {string} formType - Form type ('login', 'contact', etc.)
     */
    injectInForm: function (formType) {
      if (!config || !config.siteKey) {
        return;
      }

      var selectors = this.getSelectors(formType);
      var form = this.findElement(document, selectors);

      if (!form) {
        return;
      }

      this.injectWidget(form, {
        siteKey: config.siteKey,
        theme: config.theme,
        size: config.size,
      });
    },

    /**
     * Auto-inject widgets into all enabled forms
     */
    autoInjectForms: function () {
      var self = this;
      var enabledForms = config.enabledForms || [];

      enabledForms.forEach(function (formType) {
        self.injectInForm(formType);
      });
    },

    /**
     * Complete initialization
     */
    init: function () {
      this.initWidgets();
      this.autoInjectForms();
      this.observeDynamicWidgets();
    },
  };

  // Expose namespace globally
  window.DrsoftHCaptcha = DrsoftHCaptcha;

  /**
   * Global callback - Captcha validated
   */
  window.onHCaptchaSuccess = function (token) {
    var widgets = document.querySelectorAll(".h-captcha");

    widgets.forEach(function (widget) {
      var response = widget.querySelector('[name="h-captcha-response"]');

      if (response && response.value === token) {
        var form = DrsoftHCaptcha.findParentForm(widget);
        DrsoftHCaptcha.hideError(form);
      }
    });
  };

  /**
   * Global callback - Captcha expired
   * Required as a global function because hCaptcha API calls it via data-expired-callback
   */
  window.onHCaptchaExpired = function () {};

  /**
   * Global callback - Error
   */
  window.onHCaptchaError = function (error) {
    var prefix =
      config.i18n && config.i18n.errorPrefix
        ? config.i18n.errorPrefix
        : "[hCaptcha] Error:";
    console.warn(prefix, error);
  };

  /**
   * Wait for hCaptcha API to be loaded
   */
  function waitForHCaptchaAPI(callback, maxAttempts) {
    maxAttempts = maxAttempts || 20; // 20 attempts = 10 seconds max
    var attempts = 0;

    var checkAPI = setInterval(function () {
      attempts++;

      if (typeof hcaptcha !== "undefined") {
        clearInterval(checkAPI);
        callback();
      } else if (attempts >= maxAttempts) {
        clearInterval(checkAPI);
        console.warn(
          "[hCaptcha] API not loaded after " + maxAttempts * 500 + "ms",
        );
      }
    }, 500);
  }

  /**
   * Handle hCaptcha API ready event
   */
  function onHCaptchaAPIReady() {
    DrsoftHCaptcha.initWidgets();
    DrsoftHCaptcha.autoInjectForms();

    // Dispatch custom event for other scripts
    if (typeof Event === "function") {
      var event = new Event("hcaptchaReady");
      window.dispatchEvent(event);
    }
  }

  // Auto-initialization
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      DrsoftHCaptcha.init();

      // Wait for hCaptcha API to load
      waitForHCaptchaAPI(onHCaptchaAPIReady);
    });
  } else {
    DrsoftHCaptcha.init();

    // Wait for hCaptcha API to load
    waitForHCaptchaAPI(onHCaptchaAPIReady);
  }

  // Listen for hCaptcha API ready callback (if API loads with onload parameter)
  window.hcaptchaOnLoad = function () {
    onHCaptchaAPIReady();
  };
})();
