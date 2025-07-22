import { usePage } from '@inertiajs/react';

// Define the translations type as a nested record of strings
type TranslationType = Record<string, Record<string, string> | string>;

export function useTranslations() {
	// Type the translations from page props
	const { translations } = usePage().props as unknown as { translations: TranslationType };

	const t = (key: string): string => {
		const keys = key.split('.');
		return keys.reduce((acc: TranslationType | string, current) => {
			if (typeof acc === 'string') return acc;
			return acc[current] ?? key;
		}, translations) as string;
	};

	return { t };
}
