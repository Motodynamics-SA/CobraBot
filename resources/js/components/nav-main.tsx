import {
	SidebarMenu,
	SidebarMenuItem,
	SidebarGroup,
	SidebarGroupLabel,
	useSidebar,
	SidebarMenuButton,
} from '@/components/ui/sidebar';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { type NavItem as NavItemType } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useState, useEffect, MouseEventHandler } from 'react';

interface NavMainProps {
	items: NavItemType[];
}

export function NavMain({ items }: NavMainProps) {
	const { url } = usePage();
	const { state } = useSidebar();
	const [openGroups, setOpenGroups] = useState<number[]>(() => {
		// Initialize with groups that have defaultOpen set to true
		// or if they contain the active route
		return items
			.map((item, index) => {
				// Check if item is set to defaultOpen
				if (item.defaultOpen) return index;

				// Check if any child route is active
				if (
					item.children?.some((child) => {
						const currentUrl = new URL(window.location.href);
						const targetUrl = new URL(child.href || '', window.location.origin);

						// Check for exact match
						if (currentUrl.href === targetUrl.href) return true;

						return false;
					})
				) {
					return index;
				}

				return -1;
			})
			.filter((index) => index !== -1);
	});

	// Helper function to check if a route is active
	const isRouteActive = (href: string | undefined) => {
		if (!href) return false;

		// Get the current URL with query parameters
		const currentUrl = new URL(window.location.href);
		const targetUrl = new URL(href, window.location.origin);

		// For exact matches (including query parameters)
		if (currentUrl.href === targetUrl.href) return true;

		// For base path matches without query parameters
		if (href === url) return true;

		return false;
	};

	useEffect(() => {
		localStorage.setItem('sidebarOpenGroups', JSON.stringify(openGroups));
	}, [openGroups]);

	// Only handle child clicks for highlighting
	const handleChildClick = (parentIndex: number) => {
		if (!openGroups.includes(parentIndex)) {
			setOpenGroups([...openGroups, parentIndex]);
		}
	};

	return (
		<SidebarMenu>
			{items.map((item, index) =>
				item.children ? (
					<Collapsible
						key={index}
						className="group/collapsible"
						open={openGroups.includes(index)}
						onOpenChange={(open) => {
							setOpenGroups(
								open
									? [...openGroups, index]
									: openGroups.filter((i) => i !== index)
							);
						}}
					>
						<SidebarGroup>
							<SidebarGroupLabel asChild>
								<CollapsibleTrigger className="text-sidebar-foreground/100 hover:text-sidebar-foreground/70 flex items-center px-2 hover:cursor-pointer">
									{item.icon && <item.icon className="mr-2 h-4 w-4" />}
									{state !== 'collapsed' && (
										<>
											{item.title}
											<ChevronDown className="ml-auto h-4 w-4 transition-transform group-data-[state=open]/collapsible:rotate-180" />
										</>
									)}
								</CollapsibleTrigger>
							</SidebarGroupLabel>
							{state !== 'collapsed' && (
								<CollapsibleContent className="px-2">
									{item.children.map((child, childIndex) => (
										<SidebarMenuItem key={childIndex}>
											<SidebarMenuButton
												size="lg"
												asChild
												onClick={() => handleChildClick(index)}
											>
												<Link
													href={child.href}
													className={cn(
														'mb-1 flex items-center rounded-md px-3 py-1',
														isRouteActive(child.href)
															? 'bg-sidebar-foreground/20 text-sidebar-background'
															: 'hover:bg-sidebar-foreground/10'
													)}
												>
													{child.icon && (
														<child.icon className="mr-2 h-4 w-4" />
													)}
													{child.title}
												</Link>
											</SidebarMenuButton>
										</SidebarMenuItem>
									))}
								</CollapsibleContent>
							)}
						</SidebarGroup>
					</Collapsible>
				) : (
					<SidebarMenuItem key={index} className="px-2">
						<SidebarMenuButton
							size="lg"
							asChild
							onClick={item.onClick as MouseEventHandler | undefined}
						>
							<Link
								href={item.href}
								className={cn(
									'flex items-center rounded-md px-2 py-1',
									isRouteActive(item.href)
										? 'bg-sidebar-foreground/20 text-sidebar-background'
										: 'hover:bg-sidebar-foreground/10'
								)}
							>
								{item.icon && <item.icon className="mr-2 h-4 w-4" />}
								{state !== 'collapsed' && item.title}
							</Link>
						</SidebarMenuButton>
					</SidebarMenuItem>
				)
			)}
		</SidebarMenu>
	);
}
