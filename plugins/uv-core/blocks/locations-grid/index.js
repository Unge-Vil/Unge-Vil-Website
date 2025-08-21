( function( wp ) {
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls } = wp.blockEditor;
    const { PanelBody, RangeControl, ToggleControl } = wp.components;

    registerBlockType( 'uv/locations-grid', {
        edit: function( props ) {
            const { attributes: { columns, show_links }, setAttributes } = props;
            return [
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Settings', 'uv-core' ), initialOpen: true },
                        createElement( RangeControl, {
                            label: __( 'Columns', 'uv-core' ),
                            min: 1,
                            max: 6,
                            value: columns,
                            onChange: function( value ) { setAttributes( { columns: value } ); }
                        } ),
                        createElement( ToggleControl, {
                            label: __( 'Show Links', 'uv-core' ),
                            checked: show_links,
                            onChange: function( value ) { setAttributes( { show_links: value } ); }
                        } )
                    )
                ),
                createElement( 'p', {}, __( 'Locations Grid', 'uv-core' ) )
            ];
        },
        save: function() { return null; }
    } );
} )( window.wp );
