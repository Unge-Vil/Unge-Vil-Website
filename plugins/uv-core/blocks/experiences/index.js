( function( wp ) {
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, RangeControl, SelectControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/experiences', {
        edit: function( props ) {
            const { attributes: { count, layout }, setAttributes } = props;
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Innstillinger', 'uv-core' ), initialOpen: true },
                        createElement( RangeControl, {
                            label: __( 'Antall', 'uv-core' ),
                            min: 1,
                            max: 10,
                            value: count,
                            onChange: function( value ) { setAttributes( { count: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } ),
                        createElement( SelectControl, {
                            label: __( 'Layout', 'uv-core' ),
                            value: layout,
                            options: [
                                { label: __( 'Liste', 'uv-core' ), value: 'list' },
                                { label: __( 'Rutenett', 'uv-core' ), value: 'grid' },
                                { label: __( 'Tidslinje', 'uv-core' ), value: 'timeline' }
                            ],
                            onChange: function( value ) { setAttributes( { layout: value } ); }
                        } )
                    )
                ),
                createElement( 'div', useBlockProps(),
                    createElement( ServerSideRender, {
                        block: 'uv/experiences',
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
                                __( 'Ingen erfaringer funnet.', 'uv-core' )
                            );
                        }
                    } )
                )
            );
        },
        save: function() { return null; }
    } );
} )( window.wp );
