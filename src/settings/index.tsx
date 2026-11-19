/**
 * Settings page app.
 *
 * Mounts into the root rendered by `Block_Policy\Settings_Page`.
 */
import { createRoot } from '@wordpress/element';

import { App } from './app';
import './settings.scss';

const container = document.getElementById('block-policy-settings-root');

if (container) {
	createRoot(container).render(<App />);
}
