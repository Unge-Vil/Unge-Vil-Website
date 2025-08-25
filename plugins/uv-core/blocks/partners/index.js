( function( wp ) {
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl } = wp.components;
    const { useSelect } = wp.data;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/partners', {
        edit: function( props ) {
            const { attributes: { location, type, columns }, setAttributes } = props;
            const locations = useSelect( function( select ) {
                return select( 'core' ).getEntityRecords( 'taxonomy', 'uv_location', { per_page: -1 } );
            }, [] );
            const types = useSelect( function( select ) {
                return select( 'core' ).getEntityRecords( 'taxonomy', 'uv_partner_type', { per_page: -1 } );
            }, [] );
            const locationOptions = locations ? locations.map( function( t ) { return { label: t.name, value: t.slug }; } ) : [];
            const typeOptions = types ? types.map( function( t ) { return { label: t.name, value: t.slug }; } ) : [];
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Settings', 'uv-core' ), initialOpen: true },
                        createElement( SelectControl, {
                            label: __( 'Location', 'uv-core' ),
                            value: location,
                            options: [ { label: __( 'Select', 'uv-core' ), value: '' } ].concat( locationOptions ),
                            onChange: function( value ) { setAttributes( { location: value } ); }
                        } ),
                        createElement( SelectControl, {
                            label: __( 'Type', 'uv-core' ),
                            value: type,
                            options: [ { label: __( 'Select', 'uv-core' ), value: '' } ].concat( typeOptions ),
                            onChange: function( value ) { setAttributes( { type: value } ); }
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
                createElement( 'div', useBlockProps(),
                    createElement( ServerSideRender, {
                        block: 'uv/partners',
                        attributes: props.attributes,
                    } )
                )
            );
        },
        save: function() { return null; }
    } );
} )( window.wp );
