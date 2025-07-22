/**
 * Format a date string according to the current locale
 */
export function formatDate(
	date: string | null,
	options: Intl.DateTimeFormatOptions = {
		year: 'numeric',
		month: 'long',
		day: 'numeric',
	}
): string {
	if (!date) return '';
	return new Date(date).toLocaleDateString(document.documentElement.lang || 'en', options);
}

/**
 * Format a date string to a relative time (e.g., "2 hours ago")
 */
export function formatRelativeTime(date: string): string {
	const locale = document.documentElement.lang || 'en';
	const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
	const now = new Date();
	const diff = new Date(date).getTime() - now.getTime();

	const seconds = Math.floor(diff / 1000);
	const minutes = Math.floor(seconds / 60);
	const hours = Math.floor(minutes / 60);
	const days = Math.floor(hours / 24);

	if (Math.abs(days) > 0) return rtf.format(days, 'day');
	if (Math.abs(hours) > 0) return rtf.format(hours, 'hour');
	if (Math.abs(minutes) > 0) return rtf.format(minutes, 'minute');
	return rtf.format(seconds, 'second');
}
