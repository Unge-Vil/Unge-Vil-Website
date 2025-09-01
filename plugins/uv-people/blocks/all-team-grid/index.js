( function( wp ) {
    const { createElement: el, Fragment } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, RangeControl, FormTokenField, ToggleControl, SelectControl } = wp.components;
    const ServerSideRender = wp.serverSideRender;
    const { useSelect } = wp.data;

    const cache = {};

    function useTaxonomy( taxonomy, query = { per_page: 100 } ) {
        const key = taxonomy + JSON.stringify( query );
        return useSelect( ( select ) => {
            if ( cache[ key ] ) {
                return cache[ key ];
            }
            const core = select( 'core' );
            const terms = core.getEntityRecords( 'taxonomy', taxonomy, query );
            const result = {
                terms,
                error: core.getLastEntityRecordsError
                    ? core.getLastEntityRecordsError( 'taxonomy', taxonomy, query )
                    : null,
            };
            if ( terms ) {
                cache[ key ] = result;
            }
            return result;
        }, [] );
    }

    registerBlockType( 'uv/all-team-grid', {
        edit( props ) {
            const {
                attributes: {
                    columns,
                    locations = [],
                    allLocations,
                    showQuote,
                    showBio,
                    showEmail,
                    showAge,
                    sortBy,
                },
                setAttributes,
            } = props;

            const { terms, error } = useTaxonomy( 'uv_location' );
            const suggestions = terms ? terms.map( ( term ) => term.slug ) : [];

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __( 'Innstillinger', 'uv-people' ), initialOpen: true },
                        error
                            ? el(
                                  'p',
                                  { className: 'uv-block-placeholder' },
                                  __( 'Kunne ikke laste steder.', 'uv-people' )
                              )
                            : el( FormTokenField, {
                                  label: __( 'Steder', 'uv-people' ),
                                  value: locations,
                                  suggestions,
                                  onChange: ( value ) => setAttributes( { locations: value } ),
                                  disabled: allLocations,
                                  style: { minHeight: '40px', marginBottom: 0 },
                              } ),
                        el( ToggleControl, {
                            label: __( 'Alle steder', 'uv-people' ),
                            checked: allLocations,
                            onChange: ( value ) => setAttributes( { allLocations: value } ),
                            style: { height: '40px', marginBottom: 0 },
                        } ),
                        el( ToggleControl, {
                            label: __( 'Vis alder', 'uv-people' ),
                            checked: showAge,
                            onChange: ( value ) => setAttributes( { showAge: value } ),
                            style: { height: '40px', marginBottom: 0 },
                        } ),
                        el( ToggleControl, {
                            label: __( 'Vis sitat', 'uv-people' ),
                            checked: showQuote,
                            onChange: ( value ) => setAttributes( { showQuote: value } ),
                            style: { height: '40px', marginBottom: 0 },
                        } ),
                        el( ToggleControl, {
                            label: __( 'Vis bio', 'uv-people' ),
                            checked: showBio,
                            onChange: ( value ) => setAttributes( { showBio: value } ),
                            style: { height: '40px', marginBottom: 0 },
                        } ),
                        el( ToggleControl, {
                            label: __( 'Vis e-post', 'uv-people' ),
                            checked: showEmail,
                            onChange: ( value ) => setAttributes( { showEmail: value } ),
                            style: { height: '40px', marginBottom: 0 },
                        } ),
                        el( SelectControl, {
                            label: __( 'Sorter etter', 'uv-people' ),
                            value: sortBy,
                            options: [
                                { label: __( 'Standard', 'uv-people' ), value: 'default' },
                                { label: __( 'Alder', 'uv-people' ), value: 'age' },
                                { label: __( 'Navn', 'uv-people' ), value: 'name' },
                            ],
                            onChange: ( value ) => setAttributes( { sortBy: value } ),
                            style: { height: '40px', marginBottom: 0 },
                        } ),
                        el( RangeControl, {
                            label: __( 'Kolonner', 'uv-people' ),
                            min: 1,
                            max: 6,
                            value: columns,
                            onChange: ( value ) => setAttributes( { columns: value } ),
                            style: { height: '40px', marginBottom: 0 },
                        } )
                    )
                ),
                el( 'div', useBlockProps(),
                    el( ServerSideRender, {
                        block: 'uv/all-team-grid',
                        attributes: props.attributes,
                        LoadingResponsePlaceholder: () =>
                            el( 'p', { className: 'uv-block-placeholder' }, __( 'Laster forhåndsvisning…', 'uv-people' ) ),
                        EmptyResponsePlaceholder: () =>
                            el( 'div', { className: 'uv-block-placeholder' }, __( 'Ingen teammedlemmer funnet.', 'uv-people' ) ),
                    } )
                )
            );
        },
        save() {
            return null;
        },
    } );
} )( window.wp );

