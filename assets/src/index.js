import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import './index.scss';

/**
 * Render the Updatronix settings page once the DOM is ready.
 *
 * wp-notices is a script dependency so the default store context is available.
 */
domReady(() => {
	const rootEl = document.getElementById('updatronix-settings');
	if (!rootEl || !(rootEl instanceof HTMLElement)) {
		return;
	}

	const root = createRoot(rootEl);
	root.render(null);

	import('./js/pages/SettingsPage')
		.then(({ SettingsPage }) => {
			root.render(<SettingsPage />);
		})
		.catch(() => {
			rootEl.textContent = 'Updatronix failed to load.';
		});
});
