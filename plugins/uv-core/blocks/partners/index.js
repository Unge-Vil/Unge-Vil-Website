( function( wp ) {
    const { createElement } = wp.element;
    const { useSelect } = wp.data;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/partners', {
        edit: function( props ) {
            const { attributes: { location, type, columns }, setAttributes } = props;
            const query = { per_page: 100 };
            const {
                locations,
                types,
                locationError,
                typeError
            } = useSelect( function( select ) {
                const core = select( 'core' );
                return {
                    locations: core.getEntityRecords( 'taxonomy', 'uv_location', query ),
                    types: core.getEntityRecords( 'taxonomy', 'uv_partner_type', query ),
                    locationError: core.getLastEntityRecordsError ? core.getLastEntityRecordsError( 'taxonomy', 'uv_location', query ) : null,
                    typeError: core.getLastEntityRecordsError ? core.getLastEntityRecordsError( 'taxonomy', 'uv_partner_type', query ) : null
                };
            }, [] );
            const locationOptions = locations ? locations.map( function( t ) { return { label: t.name, value: t.slug }; } ) : [];
            const typeOptions = types ? types.map( function( t ) { return { label: t.name, value: t.slug }; } ) : [];
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Settings', 'uv-core' ), initialOpen: true },
                        locationError ?
                        createElement( 'p', { className: 'uv-block-placeholder' }, __( 'Failed to load locations.', 'uv-core' ) ) :
                        createElement( SelectControl, {
                            label: __( 'Location', 'uv-core' ),
                            value: location,
                            options: [ { label: __( 'Select', 'uv-core' ), value: '' } ].concat( locationOptions ),
                            onChange: function( value ) { setAttributes( { location: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } ),
                        typeError ?
                        createElement( 'p', { className: 'uv-block-placeholder' }, __( 'Failed to load types.', 'uv-core' ) ) :
                        createElement( SelectControl, {
                            label: __( 'Type', 'uv-core' ),
                            value: type,
                            options: [ { label: __( 'Select', 'uv-core' ), value: '' } ].concat( typeOptions ),
                            onChange: function( value ) { setAttributes( { type: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } ),
                        createElement( RangeControl, {
                            label: __( 'Columns', 'uv-core' ),
                            min: 1,
                            max: 6,
                            value: columns,
                            onChange: function( value ) { setAttributes( { columns: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } )
                    )
                ),
                createElement( 'div', useBlockProps(),
                    createElement( ServerSideRender, {
                        block: 'uv/partners',
                        attributes: props.attributes,
                        LoadingResponsePlaceholder: function() {
                            return createElement(
                                'p',
                                { className: 'uv-block-placeholder' },
                                __( 'Loading preview…', 'uv-core' )
                            );
                        }
                    } )
                )
            );
        },
        save: function() { return null; }
    } );
} )( window.wp );
