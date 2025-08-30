jQuery(function($){
    function initSelect2(){
        if ($.fn.select2) {
            $(".uv-location-select, .uv-primary-location-select").select2();
        }
    }
    initSelect2();

    $(".uv-location-select").on("change", function(){
        var $loc = $(this);
        var selected = $loc.val() || [];
        var $primary = $loc.closest("tr").find(".uv-primary-location-select");
        $primary.find("option").each(function(){
            var val = $(this).val();
            var allowed = selected.indexOf(val) !== -1;
            $(this).prop("disabled", !allowed);
            if(!allowed){
                $(this).prop("selected", false);
            }
        });
        if ($.fn.select2) {
            $primary.trigger("change.select2");
        }
    }).trigger("change");

    $(document).on("click", ".uv-avatar-button", function(e){
        e.preventDefault();
        if (!wp || !wp.media) {
            return;
        }
        var strings = typeof UVTeamManager !== "undefined" ? UVTeamManager : {
            selectAvatar: "Select Avatar",
            useImage: "Use this image"
        };
        var $wrap = $(this).closest(".uv-avatar-field");
        var frame = wp.media({
            title: strings.selectAvatar,
            library: { type: "image" },
            button: { text: strings.useImage },
            multiple: false
        });
        frame.on("select", function(){
            var attachment = frame.state().get("selection").first().toJSON();
            var url = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
            $wrap.find(".uv-avatar-preview").html("<img src=\"" + url + "\" />");
            $wrap.find(".uv-avatar-id").val(attachment.id);
            $wrap.find(".uv-avatar-remove").show();
        });
        frame.open();
    });

    $(document).on("click", ".uv-avatar-remove", function(e){
        e.preventDefault();
        var $wrap = $(this).closest(".uv-avatar-field");
        var defaultHtml = $wrap.data("default");
        $wrap.find(".uv-avatar-preview").html(defaultHtml);
        $wrap.find(".uv-avatar-id").val("");
        $(this).hide();
    });
});
