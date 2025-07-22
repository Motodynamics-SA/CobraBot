import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
	Sidebar,
	SidebarContent,
	SidebarFooter,
	SidebarHeader,
	SidebarMenu,
	SidebarMenuButton,
	SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { LayoutGrid, Users, UserPlus, FileText } from 'lucide-react';
import AppLogo from './app-logo';
import { Auth } from '@/types';
import { useTranslations } from '@/hooks/use-translations';
export function AppSidebar() {
	const { auth } = usePage().props as unknown as { auth: Auth };
	const { t } = useTranslations();

	const mainNavItems: NavItem[] = [
		{
			title: t('dashboard.title'),
			href: '/dashboard',
			icon: LayoutGrid,
		},
		{
			title: t('dashboard.data_entry'),
			href: '/price-updater/data-entry',
			icon: FileText,
		},
		auth.user?.can.view_users
			? {
					title: t('users.title'),
					children: [
						{
							title: t('users.index_page_title'),
							href: route('users.index'),
							icon: Users,
						},
						auth.user?.can.create_users
							? {
									title: t('users.actions.new'),
									href: route('users.create'),
									icon: UserPlus,
								}
							: null,
					].filter(Boolean) as NavItem[],
				}
			: null,
	].filter(Boolean) as NavItem[];

	const footerNavItems: NavItem[] = [];

	return (
		<Sidebar collapsible="icon" variant="inset">
			<SidebarHeader>
				<SidebarMenu>
					<SidebarMenuItem>
						<SidebarMenuButton size="lg" asChild>
							<AppLogo label={t('common.sidebar.title')} />
						</SidebarMenuButton>
					</SidebarMenuItem>
				</SidebarMenu>
			</SidebarHeader>

			<SidebarContent>
				<NavMain items={mainNavItems} />
			</SidebarContent>

			<SidebarFooter>
				<NavFooter items={footerNavItems} className="mt-auto" />
				<NavUser />
			</SidebarFooter>
		</Sidebar>
	);
}
