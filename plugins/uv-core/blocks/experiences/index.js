import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, RangeControl, SelectControl, ToggleControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import metadata from './block.json';
import './editor.css';
import './style.css';

const ExperiencesEditorPlaceholder = ( { count, layout, pagination, year } ) => (
    <div className="uv-block-placeholder">
        <strong>{ __( 'Erfaringer', 'uv-core' ) }</strong>
        <p>
            { __(
                'Forhandsvisning er midlertidig deaktivert i editoren for a unnga REST-feil under publisering.',
                'uv-core',
            ) }
        </p>
        <p>
            { `${ __( 'Layout', 'uv-core' ) }: ${ layout } | ${ __( 'Antall', 'uv-core' ) }: ${ count }` }
            { year ? ` | ${ __( 'Ar', 'uv-core' ) }: ${ year }` : '' }
            { pagination ? ` | ${ __( 'Last inn flere', 'uv-core' ) }` : '' }
        </p>
    </div>
);

registerBlockType( metadata.name, {
    edit( { attributes: { count, layout, pagination, year }, setAttributes } ) {
        return (
            <Fragment>
                <InspectorControls>
                    <PanelBody title={ __( 'Innstillinger', 'uv-core' ) } initialOpen>
                        <RangeControl
                            label={ __( 'Antall', 'uv-core' ) }
                            min={ 1 }
                            max={ 100 }
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
                        <SelectControl
                            label={ __( 'Ar', 'uv-core' ) }
                            value={ year }
                            options={ [
                                { label: __( 'Alle ar', 'uv-core' ), value: '' },
                            ] }
                            onChange={ ( value ) => setAttributes( { year: value } ) }
                            help={ __(
                                'Arsfiltrering ma eventuelt justeres i kode midlertidig mens vi stabiliserer editoren.',
                                'uv-core',
                            ) }
                        />
                        <ToggleControl
                            label={ __( 'Aktiver paginering', 'uv-core' ) }
                            checked={ pagination }
                            onChange={ ( value ) => setAttributes( { pagination: value } ) }
                            help={ __( 'Vis en knapp for a hente flere erfaringer.', 'uv-core' ) }
                        />
                    </PanelBody>
                </InspectorControls>

                <div { ...useBlockProps() }>
                    <ExperiencesEditorPlaceholder
                        count={ count }
                        layout={ layout }
                        pagination={ pagination }
                        year={ year }
                    />
                </div>
            </Fragment>
        );
    },
    save() {
        return null;
    },
} );
