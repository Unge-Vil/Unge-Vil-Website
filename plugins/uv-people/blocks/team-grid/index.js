( function( wp ) {
    const { createElement: el, Fragment } = wp.element;
    const { registerBlockType } = wp.blocks;
    const { __ } = wp.i18n;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, RangeControl, ToggleControl } = wp.components;
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

    registerBlockType( 'uv/team-grid', {
        edit( props ) {
            const {
                attributes: { location, columns, showAge },
                setAttributes,
            } = props;

            const { terms, error } = useTaxonomy( 'uv_location' );
            const options = terms ? terms.map( ( term ) => ( { label: term.name, value: term.slug } ) ) : [];

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
                            : el( SelectControl, {
                                  label: __( 'Sted', 'uv-people' ),
                                  value: location,
                                  options: [ { label: __( 'Velg', 'uv-people' ), value: '' }, ...options ],
                                  onChange: ( value ) => setAttributes( { location: value } ),
                                  style: { height: '40px', marginBottom: 0 },
                              } ),
                        el( ToggleControl, {
                            label: __( 'Vis alder', 'uv-people' ),
                            checked: showAge,
                            onChange: ( value ) => setAttributes( { showAge: value } ),
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
                el(
                    'div',
                    useBlockProps(),
                    el( ServerSideRender, {
                        block: 'uv/team-grid',
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
