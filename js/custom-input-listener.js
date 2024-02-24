jQuery(document).ready(function ($) {
    let recaptchaLoaded = false;

    // For login form inputs
    $('.woocommerce-form-login input').on('focus', function() {
        loadRecaptcha('login-recaptcha');
    });

    // For register form inputs
    $('.woocommerce-form-register input').on('focus', function() {
        loadRecaptcha('register-recaptcha');
    });

    function loadRecaptcha(target) {
        if (!recaptchaLoaded) {
            // Load the script once
            const script = document.createElement('script');
            script.src = 'https://www.google.com/recaptcha/api.js';
            document.body.appendChild(script);

            script.onload = function() {
                waitForRecaptchaToLoad(function() {
                    renderRecaptcha(target);
                });
            };

            recaptchaLoaded = true;
        } else {
            waitForRecaptchaToLoad(function() {
                renderRecaptcha(target);
            });
        }
    }

    function renderRecaptcha(target) {
        // Check if reCAPTCHA is already rendered in the target element
        if (!$('#' + target).hasClass('recaptcha-rendered')) {
            grecaptcha.render(target, {
                'sitekey': $('#' + target).data('sitekey')
            });
            $('#' + target).addClass('recaptcha-rendered');
        }
    }
});

function waitForRecaptchaToLoad(callback) {
    if (window.grecaptcha && typeof grecaptcha.render === 'function') {
        callback();
    } else {
        setTimeout(function() {
            waitForRecaptchaToLoad(callback);
        }, 100);
    }
}
