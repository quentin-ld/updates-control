import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';

import './index.scss';

/**
 * Render the updatronix settings page once the DOM is ready.
 * Notices use the default context wp-data and wp-notices are script dependencies.
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
