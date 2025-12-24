(function($) {
    function initUploader() {
        $('.jawda-feature-image-field').each(function() {
            var $wrapper = $(this);
            var $input = $wrapper.find('input#image_id');
            var $uploadButton = $wrapper.find('.jawda-upload-button');
            var $removeButton = $wrapper.find('.jawda-remove-button');
            var $preview = $wrapper.find('.jawda-image-preview');
            var frame;

            $uploadButton.on('click', function(e) {
                e.preventDefault();

                if (frame) {
                    frame.open();
                    return;
                }

                frame = wp.media({
                    title: $uploadButton.data('modalTitle') || $uploadButton.text(),
                    button: {
                        text: (window.JawdaFeaturedAdmin && window.JawdaFeaturedAdmin.i18n && window.JawdaFeaturedAdmin.i18n.upload) || $uploadButton.text()
                    },
                    library: {
                        type: ['image']
                    },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first();

                    if (!attachment) {
                        return;
                    }

                    attachment = attachment.toJSON();

                    if (window.JawdaFeaturedAdmin && Array.isArray(window.JawdaFeaturedAdmin.allowedMimeTypes)) {
                        if (attachment.mime && window.JawdaFeaturedAdmin.allowedMimeTypes.indexOf(attachment.mime) === -1) {
                            var message = (window.JawdaFeaturedAdmin.i18n && window.JawdaFeaturedAdmin.i18n.mimeError) || '';
                            window.alert(message || window.JawdaFeaturedAdmin.allowedMimeTypes.join(', '));
                            return;
                        }
                    }

                    $input.val(attachment.id);
                    $preview.empty();

                    if (attachment.sizes && attachment.sizes.thumbnail) {
                        $preview.append($('<img />', {
                            src: attachment.sizes.thumbnail.url,
                            alt: attachment.alt || attachment.title || ''
                        }));
                    } else if (attachment.url) {
                        $preview.append($('<img />', {
                            src: attachment.url,
                            alt: attachment.alt || attachment.title || ''
                        }));
                    }

                    $removeButton.show();
                });

                frame.open();
            });

            $removeButton.on('click', function(e) {
                e.preventDefault();
                $input.val('');
                $preview.empty();
                $removeButton.hide();
            });
        });
    }

    $(document).ready(initUploader);
})(jQuery);
