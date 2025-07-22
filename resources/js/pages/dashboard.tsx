import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, PageProps, RolesEnum } from '@/types';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Users, ShieldCheck, Clock } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/utils/format';
import { useEffect } from 'react';

interface DashboardProps extends PageProps {
	stats: DashboardStats;
	token?: string;
}

const ROLE_COLORS = {
	admin: 'bg-blue-500',
	'user-manager': 'bg-green-500',
	'registered-user': 'bg-neutral-500',
} as const satisfies Record<RolesEnum, string>;

interface DashboardStats {
	users: {
		total: number;
		active: number;
		deleted: number;
		by_role: Record<RolesEnum, number>;
		recent: {
			week: number;
			month: number;
		};
	};
	recent_users: Array<{
		id: number;
		name: string;
		email: string;
		created_at: string;
		role: RolesEnum;
	}>;
}

export default function Dashboard({ stats, token }: DashboardProps) {
	const { t } = useTranslations();

	useEffect(() => {
		if (token && token !== '') {
			// Store token in localStorage
			localStorage.setItem('auth_token', token);
		}
	}, [token]);

	const breadcrumbs: BreadcrumbItem[] = [
		{
			title: t('dashboard.title'),
			href: '/dashboard',
		},
	];
	return (
		<AppLayout breadcrumbs={breadcrumbs}>
			<Head title={t('common.app_name')} />
			<div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
				<div className="grid auto-rows-min gap-4 md:grid-cols-3">
					<Card>
						<CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
							<CardTitle className="text-sm font-medium">
								{t('dashboard.total_users')}
							</CardTitle>
							<Users className="text-primary h-4 w-4" />
						</CardHeader>
						<CardContent>
							<div className="text-2xl font-bold">{stats.users.total}</div>
							<p className="text-muted-foreground text-xs">
								{stats.users.recent.week} {t('dashboard.new_users_this_week')}
							</p>
						</CardContent>
					</Card>

					<Card>
						<CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
							<CardTitle className="text-sm font-medium">
								{t('dashboard.active_users')}
							</CardTitle>
							<ShieldCheck className="h-4 w-4 text-green-600" />
						</CardHeader>
						<CardContent>
							<div className="text-2xl font-bold">{stats.users.active}</div>
							<p className="text-muted-foreground text-xs">
								{stats.users.deleted} {t('dashboard.deleted_users')}
							</p>
						</CardContent>
					</Card>

					<Card>
						<CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
							<CardTitle className="text-sm font-medium">
								{t('dashboard.recent_activity')}
							</CardTitle>
							<Clock className="text-primary h-4 w-4" />
						</CardHeader>
						<CardContent>
							<div className="text-2xl font-bold">{stats.users.recent.month}</div>
							<p className="text-muted-foreground text-xs">
								{t('dashboard.new_users_this_month')}
							</p>
						</CardContent>
					</Card>
				</div>

				<div className="grid gap-4 md:grid-cols-2">
					<Card>
						<CardHeader>
							<CardTitle>{t('dashboard.users_by_role')}</CardTitle>
						</CardHeader>
						<CardContent>
							<div className="space-y-4">
								{Object.entries(stats.users.by_role).map(([role, count]) => (
									<div key={role} className="flex items-center">
										<div className="flex-1 space-y-1">
											<p className="text-sm font-medium leading-none">
												{t(`roles.${role}`)}
											</p>
											<p className="text-muted-foreground text-sm">
												{count} {t('dashboard.users')}
											</p>
										</div>
										<div
											className={`ml-2 h-2 w-24 rounded ${ROLE_COLORS[role as keyof typeof ROLE_COLORS]}`}
											style={{
												width: `${(count / stats.users.total) * 100}%`,
											}}
										/>
									</div>
								))}
							</div>
						</CardContent>
					</Card>

					<Card>
						<CardHeader>
							<CardTitle>{t('dashboard.recent_users')}</CardTitle>
						</CardHeader>
						<CardContent>
							<div className="space-y-8">
								{stats.recent_users.map((user) => (
									<div key={user.id} className="flex items-center">
										<div className="space-y-1">
											<p className="text-sm font-medium leading-none">
												{user.name}
											</p>
											<p className="text-muted-foreground text-sm">
												{t(`roles.${user.role}`)} ·{' '}
												{formatDate(user.created_at)}
											</p>
										</div>
									</div>
								))}
							</div>
						</CardContent>
					</Card>
				</div>
			</div>
		</AppLayout>
	);
}
