(function (wp) {
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var __ = wp.i18n.__;
    var TextControl = wp.components.TextControl;
    var SelectControl = wp.components.SelectControl;
    var withSelect = wp.data.withSelect;
    var withDispatch = wp.data.withDispatch;
    var compose = wp.compose.compose;

    var PartnerMetaPanel = compose(
        withSelect(function (select) {
            return { meta: select('core/editor').getEditedPostAttribute('meta') };
        }),
        withDispatch(function (dispatch) {
            return {
                updateMeta: function (meta) {
                    dispatch('core/editor').editPost({ meta: meta });
                }
            };
        })
    )(function (props) {
        return wp.element.createElement(
            PluginDocumentSettingPanel,
            {
                name: 'uv-partner-meta',
                title: __('Partner Details', 'uv-core'),
                className: 'uv-partner-meta-panel'
            },
            wp.element.createElement(TextControl, {
                label: __('External URL', 'uv-core'),
                value: props.meta.uv_partner_url || '',
                onChange: function (value) {
                    props.updateMeta({ uv_partner_url: value });
                }
            }),
            wp.element.createElement(SelectControl, {
                label: __('Display', 'uv-core'),
                value: props.meta.uv_partner_display || 'circle_title',
                options: [
                    { label: __('Logo only', 'uv-core'), value: 'logo_only' },
                    { label: __('Logo and title', 'uv-core'), value: 'logo_title' },
                    { label: __('Circle & title', 'uv-core'), value: 'circle_title' },
                    { label: __('Title only', 'uv-core'), value: 'title_only' }
                ],
                onChange: function (value) {
                    props.updateMeta({ uv_partner_display: value });
                }
            })
        );
    });

    registerPlugin('uv-partner-meta', {
        render: PartnerMetaPanel
    });
})(window.wp);

