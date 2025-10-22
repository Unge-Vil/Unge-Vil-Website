jQuery(function($){
    if (!$.fn.select2) {
        return;
    }

    $('.uv-user-select, .uv-post-select').each(function(){
        var $select = $(this);
        var options = {
            width: '100%'
        };

        var placeholder = $select.data('placeholder');
        if (typeof placeholder !== 'undefined') {
            options.placeholder = placeholder;
        }

        var allowClearData = $select.data('allow-clear');
        if (typeof allowClearData !== 'undefined') {
            options.allowClear = !!allowClearData;
        } else if (placeholder) {
            options.allowClear = true;
        }

        if (options.allowClear && !options.placeholder) {
            options.placeholder = '';
        }

        var closeOnSelectData = $select.data('close-on-select');
        if (typeof closeOnSelectData !== 'undefined') {
            options.closeOnSelect = !!closeOnSelectData;
        } else if ($select.prop('multiple')) {
            options.closeOnSelect = false;
        }

        $select.select2(options);
    });
});
