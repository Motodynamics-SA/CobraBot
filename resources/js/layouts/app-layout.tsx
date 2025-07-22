import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { useFlashMessages } from '@/hooks/use-flash-messages';

interface AppLayoutProps {
	children: ReactNode;
	breadcrumbs?: BreadcrumbItem[];
}

const AppLayout = ({ children, breadcrumbs, ...props }: AppLayoutProps) => {
	useFlashMessages();

	return (
		<AppLayoutTemplate breadcrumbs={breadcrumbs} {...props}>
			{children}
			<Toaster />
		</AppLayoutTemplate>
	);
};

AppLayout.displayName = 'AppLayout';

export default AppLayout;
