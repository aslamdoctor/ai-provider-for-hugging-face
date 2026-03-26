/**
 * Hugging Face connector registration for the WordPress Connectors admin screen.
 *
 * @package WordPress\HuggingFaceAiProvider
 */

import {
	__experimentalRegisterConnector as registerConnector,
	__experimentalConnectorItem as ConnectorItem,
	__experimentalDefaultConnectorSettings as DefaultConnectorSettings,
} from '@wordpress/connectors';

const { useState, useEffect, useCallback, createElement } = window.wp.element;
const { __ } = window.wp.i18n;
const { Button } = window.wp.components;
const apiFetch = window.wp.apiFetch;
const el = createElement;

const API_KEY_SETTING = 'connectors_ai_hugging_face_api_key';

/**
 * Hook to manage Hugging Face API key settings.
 */
function useHuggingFaceSettings() {
	const [ isLoading, setIsLoading ] = useState( true );
	const [ apiKey, setApiKey ] = useState( '' );

	const isConnected = ! isLoading && apiKey !== '';

	const loadSettings = useCallback( async () => {
		try {
			const result = await apiFetch( {
				path: `/wp/v2/settings?_fields=${ API_KEY_SETTING }`,
			} );
			setApiKey( result[ API_KEY_SETTING ] || '' );
		} catch {
			// Settings may not be accessible.
		} finally {
			setIsLoading( false );
		}
	}, [] );

	useEffect( () => {
		loadSettings();
	}, [ loadSettings ] );

	const saveApiKey = useCallback(
		async ( newKey ) => {
			const result = await apiFetch( {
				path: `/wp/v2/settings?_fields=${ API_KEY_SETTING }`,
				method: 'POST',
				data: { [ API_KEY_SETTING ]: newKey },
			} );

			const returned = result[ API_KEY_SETTING ] || '';
			if ( returned === apiKey && newKey !== '' ) {
				throw new Error(
					__(
						'It was not possible to save the API key.',
						'ai-provider-for-hugging-face'
					)
				);
			}
			setApiKey( returned );
		},
		[ apiKey ]
	);

	const removeApiKey = useCallback( async () => {
		await apiFetch( {
			path: `/wp/v2/settings?_fields=${ API_KEY_SETTING }`,
			method: 'POST',
			data: { [ API_KEY_SETTING ]: '' },
		} );
		setApiKey( '' );
	}, [] );

	return { isLoading, isConnected, apiKey, saveApiKey, removeApiKey };
}

/**
 * Hugging Face connector component for the Settings > Connectors page.
 *
 * @param {Object} props Component props from registerConnector.
 */
function HuggingFaceConnector( { name, description, logo } ) {
	const { isLoading, isConnected, apiKey, saveApiKey, removeApiKey } =
		useHuggingFaceSettings();
	const [ isExpanded, setIsExpanded ] = useState( false );

	if ( isLoading ) {
		return el( ConnectorItem, {
			logo,
			name,
			description,
			actionArea: el( 'span', { className: 'spinner is-active' } ),
		} );
	}

	const buttonLabel = isConnected
		? __( 'Edit', 'ai-provider-for-hugging-face' )
		: __( 'Set Up', 'ai-provider-for-hugging-face' );

	const actionButton = el(
		Button,
		{
			variant: isConnected ? 'tertiary' : 'secondary',
			size: isConnected ? undefined : 'compact',
			onClick: () => setIsExpanded( ! isExpanded ),
			'aria-expanded': isExpanded,
		},
		buttonLabel
	);

	const settingsPanel =
		isExpanded &&
		el(
			'div',
			null,
			el( DefaultConnectorSettings, {
				key: isConnected ? 'connected' : 'disconnected',
				onSave: saveApiKey,
				onRemove: removeApiKey,
				initialValue: apiKey,
				readOnly: isConnected,
				helpUrl: 'https://huggingface.co/settings/tokens',
				helpLabel: __(
					'Hugging Face Tokens',
					'ai-provider-for-hugging-face'
				),
			} )
		);

	return el(
		ConnectorItem,
		{
			logo,
			name,
			description,
			actionArea: actionButton,
		},
		settingsPanel
	);
}

registerConnector( 'ai_provider/hugging_face', {
	name: __( 'Hugging Face', 'ai-provider-for-hugging-face' ),
	description: __(
		'Text generation with open-source models via Hugging Face Inference API.',
		'ai-provider-for-hugging-face'
	),
	logo: window.huggingFaceProviderData?.logoUrl || '',
	render: HuggingFaceConnector,
} );
