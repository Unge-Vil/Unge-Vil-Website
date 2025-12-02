import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, Spinner } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import { useEntityRecords } from '@wordpress/core-data';
import metadata from './block.json';
import './editor.css';
import './style.css';

const ExperienceCard = ( { post } ) => {
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
    if ( layout !== 'list' ) {
        baseClasses.push( 'uv-card-list' );
    }
    if ( layout === 'grid' ) {
        baseClasses.push( 'uv-card-grid', 'columns-3' );
    }

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
            { posts.map( ( post ) => (
                <ExperienceCard key={ post.id } post={ post } />
            ) ) }
        </ul>
    );
};

registerBlockType( metadata.name, {
    edit( { attributes: { count, layout }, setAttributes } ) {
        const { records: posts, isResolving, hasResolved } = useEntityRecords(
            'postType',
            'uv_experience',
            {
                per_page: count,
                _embed: true,
                _fields: [ 'id', 'title', 'excerpt', 'link', 'meta', 'featured_media' ],
            }
        );

        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={ __( 'Innstillinger', 'uv-core' ) } initialOpen>
                        <RangeControl
                            label={ __( 'Antall', 'uv-core' ) }
                            min={ 1 }
                            max={ 10 }
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
                    </PanelBody>
                </InspectorControls>

                <div { ...useBlockProps() }>
                    { hasResolved && Array.isArray( posts ) ? (
                        <ExperiencesPreview posts={ posts } layout={ layout } isLoading={ false } />
                    ) : (
                        <ExperiencesPreview posts={ [] } layout={ layout } isLoading={ isResolving } />
                    ) }
                </div>
            </Fragment>
        );
    },
    save() {
        return null;
    },
} );
