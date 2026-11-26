/**
 * Settings page app.
 *
 * Mounts into the root rendered by `BlockChaperone\Settings_Page`.
 */
import { createRoot } from '@wordpress/element';

import { App } from './app';
import './settings.scss';

const container = document.getElementById('block-chaperone-settings-root');

if (container) {
	createRoot(container).render(<App />);
}
