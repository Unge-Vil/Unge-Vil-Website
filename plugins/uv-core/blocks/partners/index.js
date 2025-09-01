import fetchTerms from '../utils/fetchTerms';

( function( wp ) {
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl, ToggleControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/partners', {
        edit: function( props ) {
            const { attributes: { location, type, columns, showLocations }, setAttributes } = props;
            const query = { per_page: 100 };
            const locationData = fetchTerms( 'uv_location', query );
            const typeData = fetchTerms( 'uv_partner_type', query );
            const locations = locationData.terms;
            const types = typeData.terms;
            const locationError = locationData.error;
            const typeError = typeData.error;
            const locationOptions = locations ? locations.map( function( t ) { return { label: t.name, value: t.slug }; } ) : [];
            const typeOptions = types ? types.map( function( t ) { return { label: t.name, value: t.slug }; } ) : [];
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Innstillinger', 'uv-core' ), initialOpen: true },
                        locationError ?
                        createElement( 'p', { className: 'uv-block-placeholder' }, __( 'Kunne ikke laste steder.', 'uv-core' ) ) :
                        createElement( SelectControl, {
                            label: __( 'Sted', 'uv-core' ),
                            value: location,
                            options: [ { label: __( 'Velg', 'uv-core' ), value: '' } ].concat( locationOptions ),
                            onChange: function( value ) { setAttributes( { location: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } ),
                        typeError ?
                        createElement( 'p', { className: 'uv-block-placeholder' }, __( 'Kunne ikke laste typer.', 'uv-core' ) ) :
                        createElement( SelectControl, {
                            label: __( 'Type', 'uv-core' ),
                            value: type,
                            options: [ { label: __( 'Velg', 'uv-core' ), value: '' } ].concat( typeOptions ),
                            onChange: function( value ) { setAttributes( { type: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } ),
                        createElement( RangeControl, {
                            label: __( 'Kolonner', 'uv-core' ),
                            min: 1,
                            max: 6,
                            value: columns,
                            onChange: function( value ) { setAttributes( { columns: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } ),
                        createElement( ToggleControl, {
                            label: __( 'Vis steder', 'uv-core' ),
                            checked: showLocations,
                            onChange: function( value ) { setAttributes( { showLocations: value } ); }
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
                                __( 'Laster forhåndsvisning…', 'uv-core' )
                            );
                        },
                        EmptyResponsePlaceholder: function() {
                            return createElement(
                                'div',
                                { className: 'uv-block-placeholder' },
                                __( 'Ingen partnere funnet.', 'uv-core' )
                            );
                        }
                    } )
                )
            );
        },
        save: function() { return null; }
    } );
} )( window.wp );
