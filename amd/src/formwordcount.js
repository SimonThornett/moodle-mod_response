define(['jquery', 'mod_response/countwords'], function($, countWords) {
    const eventName = 'input.modResponseWordCount change.modResponseWordCount';

    return {
        init: function(selector) {
            $(selector + ' textarea')
                .off(eventName)
                .on(eventName, function() {
                    const text = $(this).val() || '';
                    const messageElement = $(this).closest('form').find('[data-message]');
                    const message = messageElement.data('message');

                    if (typeof message === 'string') {
                        messageElement.text(message.replace('{words}', countWords(text)));
                    }
                })
                .trigger('change.modResponseWordCount');
        }
    };
});
