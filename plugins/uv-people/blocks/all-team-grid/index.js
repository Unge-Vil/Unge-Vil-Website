( function( wp ) {
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, RangeControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/all-team-grid', {
        edit: function( props ) {
            const { attributes: { columns }, setAttributes } = props;
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Settings', 'uv-people' ), initialOpen: true },
                        createElement( RangeControl, {
                            label: __( 'Columns', 'uv-people' ),
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
                        block: 'uv/all-team-grid',
                        attributes: props.attributes,
                        LoadingResponsePlaceholder: function() {
                            return createElement(
                                'p',
                                { className: 'uv-block-placeholder' },
                                __( 'Loading preview…', 'uv-people' )
                            );
                        },
                        EmptyResponsePlaceholder: function() {
                            return createElement(
                                'div',
                                { className: 'uv-block-placeholder' },
                                __( 'No team members found.', 'uv-people' )
                            );
                        }
                    } )
                )
            );
        },
        save: function() {
            return null;
        }
    } );
} )( window.wp );
