( function( wp ) {
    var cache = {};
    function fetchTerms( taxonomy, query ) {
        query = query || { per_page: 100 };
        var cacheKey = taxonomy + JSON.stringify( query );
        return wp.data.useSelect( function( select ) {
            var core = select( 'core' );
            var terms = core.getEntityRecords( 'taxonomy', taxonomy, query );
            var error = core.getLastEntityRecordsError ? core.getLastEntityRecordsError( 'taxonomy', taxonomy, query ) : null;
            var result = { terms: terms, error: error };
            if ( terms ) {
                cache[ cacheKey ] = result;
            }
            return cache[ cacheKey ] || result;
        }, [] );
    }

    var createElement = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    var __ = wp.i18n.__;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var RangeControl = wp.components.RangeControl;
    var ServerSideRender = wp.serverSideRender;

    registerBlockType( 'uv/news', {
        edit: function( props ) {
            var location = props.attributes.location;
            var count = props.attributes.count;
            var setAttributes = props.setAttributes;
            var query = { per_page: 100 };
            var data = fetchTerms( 'uv_location', query );
            var terms = data.terms;
            var error = data.error;
            var options = terms ? terms.map( function( t ) {
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
                        },
                        EmptyResponsePlaceholder: function() {
                            return createElement(
                                'div',
                                { className: 'uv-block-placeholder' },
                                __( 'No posts found.', 'uv-core' )
                            );
                        }
                    } )
                )
            );
        },
        save: function() { return null; }
    } );
} )( window.wp );
