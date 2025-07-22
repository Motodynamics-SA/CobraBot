import '@testing-library/jest-dom';
import React from 'react';

// Mock InertiaJS
jest.mock('@inertiajs/react', () => ({
	usePage: () => ({
		props: {
			auth: {
				user: null,
			},
			ziggy: {
				url: 'http://localhost',
			},
			flash: {},
			errors: {},
		},
	}),
	Link: jest.fn(({ children }: { children: React.ReactNode }) => children),
	router: {
		get: jest.fn(),
		post: jest.fn(),
		put: jest.fn(),
		patch: jest.fn(),
		delete: jest.fn(),
		visit: jest.fn(),
	},
	Head: jest.fn(({ children }: { children: React.ReactNode }) => children),
}));

// Mock translations hook
jest.mock('@/hooks/use-translations', () => ({
	useTranslations: () => ({
		t: (key: string): string => key,
	}),
}));

// Mock global route function
// @ts-expect-error - We're setting a global in the test environment
window.route = jest.fn((name: string): string => `/${name}`);

// Setup for testing
Object.defineProperty(window, 'matchMedia', {
	writable: true,
	value: jest.fn().mockImplementation((query: string) => ({
		matches: false,
		media: query,
		onchange: null,
		addListener: jest.fn(),
		removeListener: jest.fn(),
		addEventListener: jest.fn(),
		removeEventListener: jest.fn(),
		dispatchEvent: jest.fn(),
	})),
});
