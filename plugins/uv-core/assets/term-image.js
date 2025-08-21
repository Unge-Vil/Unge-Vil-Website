jQuery(function($){
    var frame;
    $('.uv-upload').on('click', function(e){
        e.preventDefault();
        frame = wp.media({title: uvTermImage.selectImage, multiple: false});
        frame.on('select', function(){
            var att = frame.state().get('selection').first().toJSON();
            $('#uv_location_image').val(att.id);
        });
        frame.open();
    });
});
