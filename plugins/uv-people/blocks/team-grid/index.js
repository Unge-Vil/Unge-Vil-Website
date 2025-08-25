( function( wp ) {
    const { createElement, useEffect, useState } = wp.element;
    const apiFetch = wp.apiFetch;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/team-grid', {
        edit: function( props ) {
            const { attributes: { location, columns }, setAttributes } = props;
            const [ terms, setTerms ] = useState( [] );
            useEffect( function() {
                apiFetch( { path: '/wp/v2/uv_location?per_page=-1' } ).then( setTerms );
            }, [] );
            const options = terms.map( function( t ) {
                return { label: t.name, value: t.slug };
            } );
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Settings', 'uv-people' ), initialOpen: true },
                        createElement( SelectControl, {
                            label: __( 'Location', 'uv-people' ),
                            value: location,
                            options: [ { label: __( 'Select', 'uv-people' ), value: '' } ].concat( options ),
                            onChange: function( value ) { setAttributes( { location: value } ); }
                        } ),
                        createElement( RangeControl, {
                            label: __( 'Columns', 'uv-people' ),
                            min: 1,
                            max: 6,
                            value: columns,
                            onChange: function( value ) { setAttributes( { columns: value } ); }
                        } )
                    )
                ),
                createElement( 'div', useBlockProps(),
                    createElement( ServerSideRender, {
                        block: 'uv/team-grid',
                        attributes: props.attributes,
                        LoadingResponsePlaceholder: function() {
                            return createElement(
                                'p',
                                { className: 'uv-block-placeholder' },
                                __( 'Loading preview…', 'uv-people' )
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
