import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	Spinner,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Shipping zones injected by PHP.
 *
 * @see \MSM\DeliveryTable\Blocks\DeliveryTableBlock::provideEditorData()
 *
 * @return {Array<{value: string, label: string}>} Options for the zone select.
 */
function zoneOptions() {
	const zones = window.dtfsBlockEditor?.zones ?? [];

	return [ { value: '', label: __( 'All zones', 'delivery-table-for-flexible-shipping' ) }, ...zones ];
}

export default function Edit( { name, attributes, setAttributes } ) {
	const {
		zone,
		showDisabled,
		codPrefix,
		zoneHeadings,
		taxDisplay,
		includeRestOfWorld,
	} = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Table', 'delivery-table-for-flexible-shipping' ) }>
					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Shipping zone', 'delivery-table-for-flexible-shipping' ) }
						value={ zone }
						options={ zoneOptions() }
						onChange={ ( value ) =>
							setAttributes( { zone: value } )
						}
						help={ __(
							'Leave on "All zones" to render one table per zone.',
							'delivery-table-for-flexible-shipping'
						) }
					/>

					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Zone headings', 'delivery-table-for-flexible-shipping' ) }
						value={ zoneHeadings }
						options={ [
							{
								value: 'auto',
								label: __(
									'Only with several zones',
									'delivery-table-for-flexible-shipping'
								),
							},
							{ value: 'show', label: __( 'Always', 'delivery-table-for-flexible-shipping' ) },
							{ value: 'hide', label: __( 'Never', 'delivery-table-for-flexible-shipping' ) },
						] }
						onChange={ ( value ) =>
							setAttributes( { zoneHeadings: value } )
						}
					/>

					<SelectControl
						__nextHasNoMarginBottom
						label={ __( 'Tax', 'delivery-table-for-flexible-shipping' ) }
						value={ taxDisplay }
						options={ [
							{
								value: 'auto',
								label: __(
									'Follow the shop setting',
									'delivery-table-for-flexible-shipping'
								),
							},
							{
								value: 'incl',
								label: __(
									'Always including tax',
									'delivery-table-for-flexible-shipping'
								),
							},
							{
								value: 'excl',
								label: __(
									'Always excluding tax',
									'delivery-table-for-flexible-shipping'
								),
							},
						] }
						help={ __(
							'The shop setting is "Display prices during cart and checkout" in WooCommerce.',
							'delivery-table-for-flexible-shipping'
						) }
						onChange={ ( value ) =>
							setAttributes( { taxDisplay: value } )
						}
					/>

					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Cash on delivery match', 'delivery-table-for-flexible-shipping' ) }
						value={ codPrefix }
						onChange={ ( value ) =>
							setAttributes( { codPrefix: value } )
						}
						help={ __(
							'Methods whose title contains this text get their own table. Leave empty for a single table.',
							'delivery-table-for-flexible-shipping'
						) }
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Include disabled methods', 'delivery-table-for-flexible-shipping' ) }
						checked={ showDisabled }
						onChange={ ( value ) =>
							setAttributes( { showDisabled: value } )
						}
					/>

					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Include "Locations not covered by your other zones"',
							'delivery-table-for-flexible-shipping'
						) }
						checked={ includeRestOfWorld }
						onChange={ ( value ) =>
							setAttributes( { includeRestOfWorld: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<ServerSideRender
					block={ name }
					attributes={ attributes }
					LoadingResponsePlaceholder={ Spinner }
					EmptyResponsePlaceholder={ () => (
						<p>
							{ __(
								'Nothing to show yet. Check that the selected zone has Flexible Shipping methods with cost rules.',
								'delivery-table-for-flexible-shipping'
							) }
						</p>
					) }
				/>
			</div>
		</>
	);
}
