/** @type {import("prettier").Config} */
module.exports = {
	// Maximum line length before wrapping
	printWidth: 100,

	// Use 4 spaces for indentation
	tabWidth: 4,

	// Use spaces instead of tabs
	useTabs: true,

	// Add semicolons at the end of statements
	semi: true,

	// Use single quotes instead of double quotes
	singleQuote: true,

	// Object and array formatting
	trailingComma: 'es5',
	bracketSpacing: true,

	// Always include parentheses around arrow function parameters
	arrowParens: 'always',

	// Line endings
	endOfLine: 'lf',

	// Vue files formatting
	vueIndentScriptAndStyle: true,
	singleAttributePerLine: false,

	// HTML formatting
	htmlWhitespaceSensitivity: 'css',
	bracketSameLine: false,

	// Handle embedded code formatting (like CSS-in-JS)
	embeddedLanguageFormatting: 'auto',

	// Plugins for additional formatting rules
	plugins: ['prettier-plugin-tailwindcss'],

	// TailwindCSS integration
	tailwindStylesheet: './resources/scss/app.scss',
	tailwindConfig: './tailwind.config.js',
};
