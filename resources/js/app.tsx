import '../scss/app.scss';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';

// eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

import { router } from '@inertiajs/react';

createInertiaApp({
	title: (title) => `${title} - ${appName}`,
	resolve: (name) =>
		resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
	setup({ el, App, props }) {
		const root = createRoot(el);

		root.render(<App {...props} />);
		delete el.dataset.page;
	},
	progress: {
		color: '#4B5563',
	},
}).catch((err) => console.error(err));

router.on('navigate', (event) => {
	const { csrf_token } = event.detail.page.props;
	if (csrf_token && typeof csrf_token === 'string') {
		(document.querySelector('meta[name="app-csrf-token"]') as HTMLMetaElement).content =
			csrf_token;
	}
});

// This will set light / dark mode on load...
initializeTheme();
