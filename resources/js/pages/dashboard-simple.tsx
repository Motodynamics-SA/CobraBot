import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';
export default function DashboardSimple() {
	const { t } = useTranslations();

	return (
		<AppLayout>
			<Head title={t('common.app_name')} />
			<div className="p-6">
				<div className="mb-6">
					<h1 className="text-2xl font-semibold">{t('common.welcome_title')}</h1>
				</div>
				<p className="mb-6 text-gray-600">{t('common.welcome_subtitle')}</p>
			</div>
		</AppLayout>
	);
}
