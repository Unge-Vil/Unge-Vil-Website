jQuery(function($){
    var frame;
    $('#uv-avatar-upload').on('click', function(e){
        e.preventDefault();
        frame = wp.media({title: UVPeople.selectAvatar, multiple:false});
        frame.on('select', function(){
            var att = frame.state().get('selection').first().toJSON();
            $('#uv_avatar_id').val(att.id);
            $('#uv-avatar-preview').html('<img src="'+att.url+'" style="max-width:128px;border-radius:12px;">');
        });
        frame.open();
    });
    $('.uv-location-select').select2({width:'100%'});
    $('.uv-user-select').select2({width:'100%'});
    $('.uv-position-select').select2({width:'100%'});
});
