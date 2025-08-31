import fetchTerms from '../utils/fetchTerms';

( function( wp ) {
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/activities', {
        edit: function( props ) {
            const { attributes: { location }, setAttributes } = props;
            const query = { per_page: 100 };
            const { terms, error } = fetchTerms( 'uv_location', query );
            const options = terms ? terms.map( function( t ) {
                return { label: t.name, value: t.slug };
            } ) : [];
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Innstillinger', 'uv-core' ), initialOpen: true },
                        error ?
                        createElement( 'p', { className: 'uv-block-placeholder' }, __( 'Kunne ikke laste steder.', 'uv-core' ) ) :
                        createElement( SelectControl, {
                            label: __( 'Sted', 'uv-core' ),
                            value: location,
                            options: [ { label: __( 'Velg', 'uv-core' ), value: '' } ].concat( options ),
                            onChange: function( value ) { setAttributes( { location: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } )
                    )
                ),
                createElement( 'div', useBlockProps(),
                    createElement( ServerSideRender, {
                        block: 'uv/activities',
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
                                __( 'Ingen aktiviteter funnet.', 'uv-core' )
                            );
                        }
                    } )
                )
            );
        },
        save: function() { return null; }
    } );
} )( window.wp );
