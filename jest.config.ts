import type { Config } from 'jest';

const config: Config = {
	preset: 'ts-jest',
	testEnvironment: 'jsdom',
	roots: ['<rootDir>/resources/js'],
	moduleNameMapper: {
		'^@/(.*)$': '<rootDir>/resources/js/$1',
		'\\.(css|scss)$': '<rootDir>/resources/js/__mocks__/styleMock.ts',
	},
	setupFilesAfterEnv: ['<rootDir>/resources/js/test/setup.ts'],
	testRegex: '(/__tests__/.*|(\\.|/)(test|spec))\\.[jt]sx?$',
	transform: {
		'^.+\\.tsx?$': [
			'ts-jest',
			{
				tsconfig: {
					jsx: 'react-jsx',
				},
			},
		],
	},
	moduleFileExtensions: ['ts', 'tsx', 'js', 'jsx', 'json'],
	collectCoverageFrom: [
		'resources/js/**/*.{ts,tsx}',
		'!resources/js/**/*.d.ts',
		'!resources/js/test/**',
		'!resources/js/__mocks__/**',
	],
	coverageReporters: ['text', 'html'],
	testPathIgnorePatterns: ['/node_modules/', '/vendor/'],
};

export default config;
