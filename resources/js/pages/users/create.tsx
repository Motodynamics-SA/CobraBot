import { Head } from '@inertiajs/react';
import { UserForm } from './components/user-form';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { useTranslations } from '@/hooks/use-translations';

interface CreateUserProps {
	roles: {
		name: string;
		label: string;
	}[];
}

export default function Create({ roles }: CreateUserProps) {
	const { t } = useTranslations();

	return (
		<AppSidebarLayout
			breadcrumbs={[
				{ title: t('users.title'), href: route('users.index') },
				{ title: t('users.actions.new'), href: route('users.create') },
			]}
		>
			<Head title={t('users.actions.new')} />
			<div className="p-6">
				<h1 className="mb-6 text-2xl font-semibold">{t('users.actions.new_big_button')}</h1>
				<UserForm action={route('users.store')} roles={roles} />
			</div>
		</AppSidebarLayout>
	);
}
