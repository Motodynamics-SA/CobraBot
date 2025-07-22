import { usePage } from '@inertiajs/react';
import { type SharedData } from '@/types';
import AppLogoIcon from './app-logo-icon';

export default function AppLogo({ label }: { label?: string }) {
	const { appName } = usePage<SharedData>().props;

	return (
		<div className="ml-1 flex items-center gap-2">
			<div className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md">
				<AppLogoIcon className="size-5 h-8 fill-current text-black dark:text-black" />
			</div>
			<span className="truncate text-sm font-semibold">{label ?? appName}</span>
		</div>
	);
}
