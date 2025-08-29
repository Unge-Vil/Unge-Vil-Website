import fetchTerms from '../../../uv-core/blocks/utils/fetchTerms';

( function( wp ) {
    const { createElement } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, RangeControl, SelectControl, ToggleControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/all-team-grid', {
        edit: function( props ) {
            const { attributes: { columns, locations = [], allLocations }, setAttributes } = props;
            const query = { per_page: 100 };
            const { terms, error } = fetchTerms( 'uv_location', query );
            const options = terms ? terms.map( function( t ) { return { label: t.name, value: t.slug }; } ) : [];
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Settings', 'uv-people' ), initialOpen: true },
                        error ?
                        createElement( 'p', { className: 'uv-block-placeholder' }, __( 'Failed to load locations.', 'uv-people' ) ) :
                        createElement( SelectControl, {
                            multiple: true,
                            label: __( 'Locations', 'uv-people' ),
                            value: locations,
                            options: options,
                            onChange: function( value ) { setAttributes( { locations: value } ); },
                            disabled: allLocations,
                            style: { minHeight: '40px', marginBottom: 0 }
                        } ),
                        createElement( ToggleControl, {
                            label: __( 'All locations', 'uv-people' ),
                            checked: allLocations,
                            onChange: function( value ) { setAttributes( { allLocations: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } ),
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
