import fetchTerms from '../utils/fetchTerms';

( function( wp ) {
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/news', {
        edit: function( props ) {
            const { attributes: { location, count }, setAttributes } = props;
            const query = { per_page: 100 };
            const { terms, error } = fetchTerms( 'uv_location', query );
            const options = terms ? terms.map( function( t ) {
                return { label: t.name, value: t.slug };
            } ) : [];
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Settings', 'uv-core' ), initialOpen: true },
                        error ?
                        createElement( 'p', { className: 'uv-block-placeholder' }, __( 'Failed to load locations.', 'uv-core' ) ) :
                        createElement( SelectControl, {
                            label: __( 'Location', 'uv-core' ),
                            value: location,
                            options: [ { label: __( 'Select', 'uv-core' ), value: '' } ].concat( options ),
                            onChange: function( value ) { setAttributes( { location: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } ),
                        createElement( RangeControl, {
                            label: __( 'Count', 'uv-core' ),
                            min: 1,
                            max: 10,
                            value: count,
                            onChange: function( value ) { setAttributes( { count: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } )
                    )
                ),
                createElement( 'div', useBlockProps(),
                    createElement( ServerSideRender, {
                        block: 'uv/news',
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
