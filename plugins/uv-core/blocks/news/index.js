( function( wp ) {
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl } = wp.components;
    const { useSelect } = wp.data;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/news', {
        edit: function( props ) {
            const { attributes: { location, count }, setAttributes } = props;
            const terms = useSelect( function( select ) {
                return select( 'core' ).getEntityRecords( 'taxonomy', 'uv_location', { per_page: -1 } );
            }, [] );
            const options = terms ? terms.map( function( t ) {
                return { label: t.name, value: t.slug };
            } ) : [];
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Settings', 'uv-core' ), initialOpen: true },
                        createElement( SelectControl, {
                            label: __( 'Location', 'uv-core' ),
                            value: location,
                            options: [ { label: __( 'Select', 'uv-core' ), value: '' } ].concat( options ),
                            onChange: function( value ) { setAttributes( { location: value } ); }
                        } ),
                        createElement( RangeControl, {
                            label: __( 'Count', 'uv-core' ),
                            min: 1,
                            max: 10,
                            value: count,
                            onChange: function( value ) { setAttributes( { count: value } ); }
                        } )
                    )
                ),
                createElement( 'div', useBlockProps(),
                    createElement( ServerSideRender, {
                        block: 'uv/news',
                        attributes: props.attributes,
                    } )
                )
            );
        },
        save: function() { return null; }
    } );
} )( window.wp );
