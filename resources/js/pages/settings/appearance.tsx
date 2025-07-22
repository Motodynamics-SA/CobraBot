import { Head } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';
import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

export default function Appearance() {
	const { t } = useTranslations();

	const breadcrumbs: BreadcrumbItem[] = [
		{
			title: t('settings.appearance.title'),
			href: '/settings/appearance',
		},
	];

	return (
		<AppLayout breadcrumbs={breadcrumbs}>
			<Head title={t('settings.appearance.title')} />

			<SettingsLayout>
				<div className="space-y-6">
					<HeadingSmall
						title={t('settings.appearance.title')}
						description={t('settings.appearance.description')}
					/>
					<AppearanceTabs />
				</div>
			</SettingsLayout>
		</AppLayout>
	);
}
