const { useSelect } = wp.data;
const cache = {};

/**
 * Fetch taxonomy terms with basic caching.
 *
 * @param {string} taxonomy Taxonomy name.
 * @param {Object} query     Optional query args.
 * @returns {Object}         Object with terms and error.
 */
export default function fetchTerms( taxonomy, query ) {
    query = query || { per_page: 100 };
    const cacheKey = taxonomy + JSON.stringify( query );
    return useSelect( function( select ) {
        const core = select( 'core' );
        const terms = core.getEntityRecords( 'taxonomy', taxonomy, query );
        const error = core.getLastEntityRecordsError ? core.getLastEntityRecordsError( 'taxonomy', taxonomy, query ) : null;
        const result = { terms, error };
        if ( terms ) {
            cache[ cacheKey ] = result;
        }
        return cache[ cacheKey ] || result;
    }, [ taxonomy, JSON.stringify( query ) ] );
}
