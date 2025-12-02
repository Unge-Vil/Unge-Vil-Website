import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, Spinner, ToggleControl } from '@wordpress/components';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { useEntityRecords } from '@wordpress/core-data';
import metadata from './block.json';
import './editor.css';
import './style.css';

const getExperienceYear = ( post ) => {
    const metaDates = post.meta?.uv_experience_dates ?? '';
    const matchedYear = metaDates.match( /\b(\d{4})\b/ );

    if ( matchedYear?.[ 1 ] ) {
        return matchedYear[ 1 ];
    }

    if ( post.date ) {
        const publishedDate = new Date( post.date );

        if ( ! Number.isNaN( publishedDate.getFullYear() ) ) {
            return String( publishedDate.getFullYear() );
        }
    }

    return '';
};

const ExperienceCard = ( { post, year } ) => {
    const metaOrg = post.meta?.uv_experience_org;
    const metaDates = post.meta?.uv_experience_dates;
    const thumbnail = post._embedded?.['wp:featuredmedia']?.[ 0 ]?.source_url;

    return (
        <li className="uv-card uv-card--experience">
            { thumbnail ? (
                <img src={ thumbnail } alt={ post.title.rendered } />
            ) : (
                <div className="uv-card-icon" aria-hidden="true">
                    <svg
                        viewBox="0 0 24 24"
                        role="img"
                        focusable="false"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M12 3.75a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.5"
                        />
                        <path
                            d="M5.25 19.5a6.75 6.75 0 0 1 13.5 0"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.5"
                            strokeLinecap="round"
                        />
                    </svg>
                </div>
            ) }
            <div className="uv-card-body">
                <div className="uv-card-meta__year">{ year }</div>
                <h3>{ post.title.rendered }</h3>
                { ( metaOrg || metaDates ) && (
                    <div className="uv-card-meta">
                        { metaOrg && <div className="uv-card-meta__org">{ metaOrg }</div> }
                        { metaDates && <div className="uv-card-meta__dates">{ metaDates }</div> }
                    </div>
                ) }
                { post.excerpt?.rendered && (
                    <div className="uv-card-excerpt">
                        <span dangerouslySetInnerHTML={ { __html: post.excerpt.rendered } } />
                    </div>
                ) }
            </div>
        </li>
    );
};

const ExperiencesPreview = ( { posts, layout, isLoading } ) => {
    const baseClasses = [ 'uv-experiences', `uv-experiences--${ layout }` ];
    const groupListClasses = [ 'uv-experiences__year-list' ];

    if ( layout !== 'list' ) {
        groupListClasses.push( 'uv-card-list' );
    }
    if ( layout === 'grid' ) {
        groupListClasses.push( 'uv-card-grid', 'columns-3' );
    }

    const groupedPosts = Object.entries(
        ( posts ?? [] ).reduce( ( groups, post ) => {
            const year = getExperienceYear( post );

            if ( ! groups[ year ] ) {
                groups[ year ] = [];
            }

            groups[ year ].push( post );
            return groups;
        }, {} ),
    )
        .sort( ( [ yearA ], [ yearB ] ) => yearB.localeCompare( yearA ) )
        .map( ( [ year, items ] ) => ( { year, items } ) );

    if ( isLoading ) {
        return (
            <p className="uv-block-placeholder">
                <Spinner /> { __( 'Laster forhåndsvisning…', 'uv-core' ) }
            </p>
        );
    }

    if ( ! posts?.length ) {
        return (
            <div className="uv-block-placeholder">
                { __( 'Ingen erfaringer funnet.', 'uv-core' ) }
            </div>
        );
    }

    return (
        <ul className={ baseClasses.join( ' ' ) }>
            { groupedPosts.map( ( { year, items } ) => (
                <li key={ year } className="uv-experiences__year-group">
                    <div className="uv-experiences__year-heading">{ year }</div>
                    <ul className={ groupListClasses.join( ' ' ) }>
                        { items.map( ( post ) => (
                            <ExperienceCard
                                key={ post.id }
                                post={ post }
                                year={ getExperienceYear( post ) }
                            />
                        ) ) }
                    </ul>
                </li>
            ) ) }
        </ul>
    );
};

registerBlockType( metadata.name, {
    edit( { attributes: { count, layout, pagination }, setAttributes } ) {
        const [ page, setPage ] = useState( 1 );
        const [ loadedPosts, setLoadedPosts ] = useState( [] );
        const {
            records: posts,
            isResolving,
            hasResolved,
            totalPages,
        } = useEntityRecords(
            'postType',
            'uv_experience',
            {
                per_page: count,
                page,
                _embed: true,
                _fields: [ 'id', 'title', 'excerpt', 'link', 'meta', 'featured_media', 'date' ],
            }
        );

        useEffect( () => {
            setPage( 1 );
            setLoadedPosts( [] );
        }, [ count, pagination ] );

        useEffect( () => {
            if ( ! hasResolved || ! Array.isArray( posts ) ) {
                return;
            }

            setLoadedPosts( ( current ) => {
                if ( page === 1 ) {
                    return posts;
                }

                const seenIds = new Set( current.map( ( post ) => post.id ) );
                const merged = [ ...current ];
                posts.forEach( ( post ) => {
                    if ( ! seenIds.has( post.id ) ) {
                        merged.push( post );
                    }
                } );

                return merged;
            } );
        }, [ posts, hasResolved, page ] );

        const hasMorePages = pagination && ( totalPages ? page < totalPages : false );

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={ __( 'Innstillinger', 'uv-core' ) } initialOpen>
                        <RangeControl
                            label={ __( 'Antall', 'uv-core' ) }
                            min={ 1 }
                            max={ 20 }
                            value={ count }
                            onChange={ ( value ) => setAttributes( { count: value } ) }
                        />
                        <SelectControl
                            label={ __( 'Layout', 'uv-core' ) }
                            value={ layout }
                            onChange={ ( value ) => setAttributes( { layout: value } ) }
                            options={ [
                                { label: __( 'Liste', 'uv-core' ), value: 'list' },
                                { label: __( 'Rutenett', 'uv-core' ), value: 'grid' },
                                { label: __( 'Tidslinje', 'uv-core' ), value: 'timeline' },
                            ] }
                        />
                        <ToggleControl
                            label={ __( 'Aktiver paginering', 'uv-core' ) }
                            checked={ pagination }
                            onChange={ ( value ) => setAttributes( { pagination: value } ) }
                            help={ __( 'Vis en knapp for å hente flere erfaringer.', 'uv-core' ) }
                        />
                    </PanelBody>
                </InspectorControls>

                <div { ...useBlockProps() }>
                    { hasResolved && Array.isArray( posts ) ? (
                        <ExperiencesPreview
                            posts={ loadedPosts }
                            layout={ layout }
                            isLoading={ false }
                        />
                    ) : (
                        <ExperiencesPreview posts={ [] } layout={ layout } isLoading={ isResolving } />
                    ) }
                    { pagination && (
                        <div className="uv-block-pagination">
                            <button
                                className="uv-button"
                                type="button"
                                disabled={ isResolving || ! hasMorePages }
                                onClick={ () => setPage( ( value ) => value + 1 ) }
                            >
                                { hasMorePages
                                    ? __( 'Last inn flere', 'uv-core' )
                                    : __( 'Alle erfaringer er lastet inn', 'uv-core' ) }
                            </button>
                        </div>
                    ) }
                </div>
            </Fragment>
        );
    },
    save() {
        return null;
    },
} );
