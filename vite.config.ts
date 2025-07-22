import path from 'path';
import fs from 'fs';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import dotenv from 'dotenv';
import { defineConfig, loadEnv } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import { NodePackageImporter } from 'sass-embedded';
import manifestSRI from 'vite-plugin-manifest-sri';

interface ImportMetaEnv {
	readonly VITE_DEV_URL: string;
	readonly VITE_APP_PORT: string;
	readonly VITE_DEV_PORT: string;
	readonly APP_DEVELOPMENT_ENV: string;
	readonly VITE_NOCAPTCHA_SITEKEY: string;
}

/**
 * Check if the additional environment file should be loaded.
 */
function shouldLoadAdditionalEnv(mainEnv): boolean {
	const isLocalEnv = mainEnv.APP_ENV === 'local';
	return (
		(mainEnv.APP_DEVELOPMENT_ENV && mainEnv.APP_DEVELOPMENT_ENV.length > 1 && isLocalEnv) ||
		false
	);
}

// eslint-disable-next-line @typescript-eslint/no-unused-vars
export default defineConfig(({ mode, command }) => {
	// Load the main .env file (Vite's built-in function)
	const mainEnv = loadEnv(mode, process.cwd(), '');

	// Determine which additional .env file to load
	const developmentEnv = mainEnv.APP_DEVELOPMENT_ENV ?? 'native'; // Default to .env.native

	let additionalEnv = {};
	if (shouldLoadAdditionalEnv(mainEnv)) {
		const additionalEnvFile = path.resolve(process.cwd(), `.env.${developmentEnv}`);
		if (fs.existsSync(additionalEnvFile)) {
			console.log(`Loading additional environment variables from ${additionalEnvFile}`);
			additionalEnv = dotenv.parse(fs.readFileSync(additionalEnvFile));

			// Manually inject additionalEnv into process.env
			for (const [key, value] of Object.entries(additionalEnv)) {
				process.env[key] = value as string;
			}
		} else {
			console.warn(`Additional environment file does not exist: ${additionalEnvFile}`);
		}
	}

	// Merge environment variables (additionalEnv **overrides** mainEnv)
	const env: ImportMetaEnv = { ...mainEnv, ...additionalEnv } as ImportMetaEnv;

	const DEV_URL = env.VITE_DEV_URL ?? 'http://localhost';
	const APP_PORT = env.VITE_APP_PORT ?? '8000';
	const DEV_PORT = env.VITE_DEV_PORT ?? '5179';

	const origins = [
		`${DEV_URL}`, // Laravel app URL
		`${DEV_URL}:${APP_PORT}`, // Laravel served over HTTPS (DDEV)
		`${DEV_URL}:${DEV_PORT}`, // Vite dev server
	];

	if (DEV_URL.includes('localhost')) {
		// Add 127.0.0.1 when DEV_URL is localhost
		origins.push('http://127.0.0.1:' + APP_PORT);
	}

	// Determine WebSocket protocol based on DEV_URL
	// Ensures Hot Module Reloading (HMR) works with HTTPS
	const wsProtocol = DEV_URL.startsWith('https') ? 'wss' : 'ws';

	return {
		plugins: [
			laravel({
				input: 'resources/js/app.tsx',
				ssr: 'resources/js/ssr.tsx',
				refresh: true,
			}),
			react(),
			tailwindcss(),
			manifestSRI(),
		],
		server: {
			host: '0.0.0.0',
			port: Number(DEV_PORT),
			strictPort: true,
			origin: `${DEV_URL}:${DEV_PORT}`,
			cors: {
				origin: origins,
				methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
				allowedHeaders: ['Content-Type', 'Authorization'],
				credentials: true,
			},
			hmr: {
				protocol: wsProtocol,
				host: new URL(DEV_URL).hostname, // Extracts the hostname
				port: Number(DEV_PORT),
			},
		},
		css: {
			postcss: './postcss.config.js',
			preprocessorOptions: {
				scss: {
					api: 'modern-compiler',
					silenceDeprecations: [
						'mixed-decls',
						'color-functions',
						'import',
						'global-builtin',
					],
					importers: [new NodePackageImporter()],
				},
			},
			devSourcemap: true,
		},
		resolve: {
			alias: {
				'@fontsource-variable': path.resolve(
					__dirname,
					'node_modules/@fontsource-variable'
				),
				'ziggy-js': path.resolve(__dirname, 'vendor/tightenco/ziggy'),
			},
		},
	};
});
