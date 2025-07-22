import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { PageProps, User } from '@/types';
import { Head } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';

interface ShowUserProps extends PageProps {
	user: User;
}

export default function Show({ user }: ShowUserProps) {
	const { t } = useTranslations();

	return (
		<AppSidebarLayout
			breadcrumbs={[
				{ title: t('users.title'), href: route('users.index') },
				{ title: user.name, href: route('users.show', user.id) },
			]}
		>
			<Head title={user.name} />
			<div className="p-6">
				<h1 className="mb-6 text-2xl font-semibold">{user.name}</h1>
				<div className="space-y-4">
					<div>
						<p className="text-muted-foreground text-sm font-medium">
							{t('users.labels.email')}
						</p>
						<p>{user.email}</p>
					</div>
					<div>
						<p className="text-muted-foreground text-sm font-medium">
							{t('users.labels.role')}
						</p>
						<p>{t(`roles.${user.role.replace('-', '_')}`)}</p>
					</div>
				</div>
			</div>
		</AppSidebarLayout>
	);
}
