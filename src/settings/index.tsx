/**
 * Settings page app.
 *
 * Mounts into the root rendered by `BlockPolicy\Settings_Page`.
 */
import { createRoot } from '@wordpress/element';

import { App } from './app';
import './settings.scss';

const container = document.getElementById('blockpolicy-settings-root');

if (container) {
	createRoot(container).render(<App />);
}
