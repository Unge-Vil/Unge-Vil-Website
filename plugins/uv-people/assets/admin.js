jQuery(function($){
    var frame;
    $('#uv-avatar-upload').on('click', function(e){
        e.preventDefault();
        frame = wp.media({title: UVPeople.selectAvatar, multiple:false, button:{ text: UVPeople.useImage }});
        frame.on('select', function(){
            var att = frame.state().get('selection').first().toJSON();
            $('#uv_avatar_id').val(att.id);
            $('#uv-avatar-preview').html('<img src="'+att.url+'" style="max-width:128px;border-radius:12px;">');
            $('#uv-avatar-remove').show();
        });
        frame.open();
    });
    $('#uv-avatar-remove').on('click', function(e){
        e.preventDefault();
        $('#uv_avatar_id').val('');
        $('#uv-avatar-preview').html('');
        $(this).hide();
    });
    if ($.fn.select2) {
        $('.uv-location-select').select2({width:'100%'});
    }
    if ($.fn.select2) {
        $('.uv-user-select').select2({width:'100%'});
    }
    if ($.fn.select2) {
        $('.uv-position-select').select2({width:'100%'});
    }
    var $loc = $('#uv_locations');
    var $primary = $('#uv_primary_locations');
    if($loc.length && $primary.length){
        function syncPrimary(){
            var selected = $loc.val() || [];
            $primary.find('option').each(function(){
                var val = $(this).val();
                var allowed = selected.indexOf(val) !== -1;
                $(this).prop('disabled', !allowed);
                if(!allowed){
                    $(this).prop('selected', false);
                }
            });
            $primary.trigger('change.select2');
        }
        $loc.on('change', syncPrimary);
        syncPrimary();
    }
    if($('#uv-member-sortable').length){
        $('#uv-member-sortable').sortable({handle:'.uv-handle'});
    }
});
