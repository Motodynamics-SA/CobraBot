import { Head } from '@inertiajs/react';
import { UserForm } from './components/user-form';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { useTranslations } from '@/hooks/use-translations';
import { User } from '@/types';

interface EditUserProps {
	user: User;
	roles: {
		name: string;
		label: string;
	}[];
}

export default function Edit({ user, roles }: EditUserProps) {
	const { t } = useTranslations();

	return (
		<AppSidebarLayout
			breadcrumbs={[
				{ title: t('users.title'), href: route('users.index') },
				{ title: t('users.actions.edit'), href: route('users.edit', user.id) },
			]}
		>
			<Head title={t('users.actions.edit')} />
			<div className="p-6">
				<h1 className="mb-6 text-2xl font-semibold">
					{t('users.actions.edit_big_button')}
				</h1>
				<UserForm user={user} action={route('users.update', user.id)} roles={roles} />
			</div>
		</AppSidebarLayout>
	);
}
