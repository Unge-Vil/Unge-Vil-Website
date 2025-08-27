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

    registerBlockType( 'uv/partners', {
        edit: function( props ) {
            var location = props.attributes.location;
            var type = props.attributes.type;
            var columns = props.attributes.columns;
            var setAttributes = props.setAttributes;
            var query = { per_page: 100 };
            var locationData = fetchTerms( 'uv_location', query );
            var typeData = fetchTerms( 'uv_partner_type', query );
            var locations = locationData.terms;
            var types = typeData.terms;
            var locationError = locationData.error;
            var typeError = typeData.error;
            var locationOptions = locations ? locations.map( function( t ) { return { label: t.name, value: t.slug }; } ) : [];
            var typeOptions = types ? types.map( function( t ) { return { label: t.name, value: t.slug }; } ) : [];
            return createElement( wp.element.Fragment, {},
                createElement( InspectorControls, {},
                    createElement( PanelBody, { title: __( 'Settings', 'uv-core' ), initialOpen: true },
                        locationError ?
                        createElement( 'p', { className: 'uv-block-placeholder' }, __( 'Failed to load locations.', 'uv-core' ) ) :
                        createElement( SelectControl, {
                            label: __( 'Location', 'uv-core' ),
                            value: location,
                            options: [ { label: __( 'Select', 'uv-core' ), value: '' } ].concat( locationOptions ),
                            onChange: function( value ) { setAttributes( { location: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } ),
                        typeError ?
                        createElement( 'p', { className: 'uv-block-placeholder' }, __( 'Failed to load types.', 'uv-core' ) ) :
                        createElement( SelectControl, {
                            label: __( 'Type', 'uv-core' ),
                            value: type,
                            options: [ { label: __( 'Select', 'uv-core' ), value: '' } ].concat( typeOptions ),
                            onChange: function( value ) { setAttributes( { type: value } ); },
                            style: { height: '40px', marginBottom: 0 }
                        } ),
                        createElement( RangeControl, {
                            label: __( 'Columns', 'uv-core' ),
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
                        block: 'uv/partners',
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
                                __( 'No partners found.', 'uv-core' )
                            );
                        }
                    } )
                )
            );
        },
        save: function() { return null; }
    } );
} )( window.wp );
