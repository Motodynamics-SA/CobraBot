import js from '@eslint/js';
import prettier from 'eslint-config-prettier';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import * as tseslint from 'typescript-eslint';

/** @type {Linter.Config[]} */
// Add a declare for the global route function for TypeScript
declare global {
	function route(name: string, params?: Record<string, any>): string;
}

export default tseslint.config(
	// JavaScript base config
	js.configs.recommended,

	// TypeScript linting with type checking
	tseslint.configs.recommendedTypeChecked,
	{
		languageOptions: {
			globals: {
				...globals.browser,
				...globals.jest,
				route: true,
			},
			parserOptions: {
				project: './tsconfig.json',
				tsconfigRootDir: import.meta.dirname,
			},
		},
	},

	// React linting with Flat Config
	{
		plugins: {
			react,
		},
		rules: {
			...react.configs.flat.recommended?.rules,
			...react.configs.flat['jsx-runtime']?.rules,
			'react/react-in-jsx-scope': 'off', // Not needed in React 17+
			'react/prop-types': 'off', // Not needed when using TypeScript
			'react/no-unescaped-entities': 'off', // Prevents issues with JSX & special characters
		},
		settings: {
			react: {
				version: 'detect',
			},
		},
	},

	// React Hooks linting
	{
		plugins: {
			'react-hooks': reactHooks,
		},
		rules: {
			'react-hooks/rules-of-hooks': 'error', // Ensure hooks are used correctly
			'react-hooks/exhaustive-deps': 'warn', // Warn about missing dependencies in useEffect
		},
	},

	// Global ignores (applied to all configurations)
	{
		ignores: [
			'vendor/**',
			'node_modules/**',
			'public/**',
			'bootstrap/ssr/**',
			'tailwind.config.js',
			'postcss.config.js',
			'prettier.config.cjs',
			'eslint.config.ts',
			'resources/js/ziggy.js',
			'vite.config.ts',
		],
	},

	// Special rules for test files
	{
		files: ['**/__tests__/**/*.ts', '**/__tests__/**/*.tsx', '**/*.test.ts', '**/*.test.tsx'],
		languageOptions: {
			globals: {
				...globals.jest,
			},
		},
		rules: {
			// Relax some rules for test files
			'@typescript-eslint/no-explicit-any': 'off',
			'@typescript-eslint/unbound-method': 'off',
		},
	},

	// Apply Prettier last to avoid conflicts
	prettier
);
