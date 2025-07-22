import React from 'react';
import { Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useTranslations } from '@/hooks/use-translations';

export default function Navbar() {
	const { auth } = usePage<PageProps>().props;
	const { t } = useTranslations();
	const { post } = useForm();
	const canAccessDashboard = auth.user?.can['create_users'];

	const handleLogout = (e: React.FormEvent) => {
		e.preventDefault();
		post(route('logout'));
	};

	return (
		<nav className="border-border bg-background fixed top-0 z-50 w-full border-b">
			<div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
				<div className="flex h-16 items-center justify-between">
					<div className="flex items-center">
						<Link href="/" className="text-foreground text-xl font-bold">
							{t('common.app_name')}
						</Link>
					</div>

					<div className="flex items-center gap-4">
						{auth.user ? (
							<>
								{canAccessDashboard && (
									<Link href={route('dashboard')}>
										<Button variant="ghost">{t('navbar.dashboard')}</Button>
									</Link>
								)}
								<form onSubmit={handleLogout}>
									<Button type="submit" variant="ghost">
										{t('navbar.logout')}
									</Button>
								</form>
							</>
						) : (
							<Link href={route('login')}>
								<Button variant="ghost">{t('navbar.login')}</Button>
							</Link>
						)}
					</div>
				</div>
			</div>
		</nav>
	);
}
