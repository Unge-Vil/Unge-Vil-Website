( function( wp ) {
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl } = wp.components;
    const { useSelect } = wp.data;

    registerBlockType( 'uv/activities', {
        edit: function( props ) {
            const { attributes: { location, columns }, setAttributes } = props;
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
                            label: __( 'Columns', 'uv-core' ),
                            min: 1,
                            max: 6,
                            value: columns,
                            onChange: function( value ) { setAttributes( { columns: value } ); }
                        } )
                    )
                ),
                createElement( 'div', useBlockProps(), __( 'Activities', 'uv-core' ) )
            );
        },
        save: function() { return null; }
    } );
} )( window.wp );
