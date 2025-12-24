jQuery(document).ready(function($) {
    // --- CONFLICT RESOLUTION: FINAL & DEFINITIVE FIX ---
    // The old script.js uses a delegated event handler. We must attach our own handler
    // and immediately stop propagation to prevent the old, conflicting handler from running.

    let currentForm;
    const responseBox = $('.responsebox');
    const responseHere = $('.responsehere');
    const submittingText = () => (isRtl() ? 'جار الإرسال...' : 'Sending...');

    function isRtl() {
        return $('html').attr('dir') === 'rtl';
    }

    function validateEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }

    function validatePhone(phone) {
        const re = /^[0-9]{11,17}$/;
        return re.test(String(phone));
    }

    // We attach our new handler to the document to ensure it fires.
    $(document).on('submit', 'form.siteform', function(e) {
        // --- THE DEFINITIVE FIX ---
        // Stop the default action AND prevent any other handlers from running.
        e.preventDefault();
        e.stopImmediatePropagation();

        currentForm = $(this);
        const formElement = currentForm.get(0);
        const submitButton = currentForm.find('button[type="submit"], input[type="submit"]').first();
        const originalButtonText = submitButton.is('input') ? submitButton.val() : submitButton.text();

        responseBox.removeClass('active');

        if (!formElement.checkValidity()) {
            formElement.reportValidity();
            return;
        }

        const phone = currentForm.find('input[name="phone"]').val();
        const email = currentForm.find('input[name="email"]').val();

        if (!validatePhone(phone)) {
            responseHere.text(isRtl() ? 'برجاء التأكد من ادخال رقم هاتف صحيح.' : 'Please make sure to enter a valid phone number.');
            responseBox.addClass('active');
            return;
        }

        if (email.trim() && !validateEmail(email)) {
            responseHere.text(isRtl() ? 'برجاء التأكد من ادخال بريد الكتروني صحيح.' : 'Please make sure to enter a valid email.');
            responseBox.addClass('active');
            return;
        }

        if (currentForm.data('submitting')) {
            return;
        }

        currentForm.data('submitting', true);
        if (submitButton.length) {
            submitButton.prop('disabled', true);
            if (submitButton.is('input')) {
                submitButton.val(submittingText());
            } else {
                submitButton.text(submittingText());
            }
        }

        $.ajax({
            type: 'POST',
            url: global.ajax,
            data: currentForm.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    responseHere.text(response.data.message);
                    responseBox.addClass('active');
                }
            },
            error: function(xhr) {
                const serverMessage = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
                const fallbackMessage = isRtl()
                    ? 'حدث خطأ غير متوقع. يرجى المحاولة مرة أخرى.'
                    : 'An unexpected error occurred. Please try again.';
                responseHere.text(serverMessage || fallbackMessage);
                responseBox.addClass('active');
            },
            complete: function() {
                if (submitButton.length) {
                    submitButton.prop('disabled', false);
                    if (submitButton.is('input')) {
                        submitButton.val(originalButtonText);
                    } else {
                        submitButton.text(originalButtonText);
                    }
                }
                currentForm.data('submitting', false);
                currentForm = null;
            }
        });
    });

    $('.responsecolse').on('click', function() {
        responseBox.removeClass('active');
    });

});
