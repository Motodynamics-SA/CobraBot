import AppLogoIcon from '@/components/app-logo-icon';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

export default function AuthCardLayout({
	children,
	title,
	description,
}: PropsWithChildren<{
	name?: string;
	title?: string;
	description?: string;
}>) {
	return (
		<div className="bg-muted flex min-h-svh flex-col items-center justify-center gap-6 sm:p-2 md:p-5 lg:p-6">
			<div className="flex w-full max-w-xl flex-col gap-6">
				<Link
					href={route('home')}
					className="flex items-center gap-2 self-center font-medium"
				>
					<div className="flex items-center gap-6">
						<div className="h-22 w-22 flex items-center justify-center rounded-md">
							<AppLogoIcon className="size-22 h-22 fill-current text-[var(--foreground)] dark:text-white" />
						</div>
					</div>
				</Link>

				<div className="flex flex-col gap-6">
					<Card className="rounded-xl">
						<CardHeader className="px-10 pb-0 pt-8 text-center">
							<CardTitle className="text-2xl">{title}</CardTitle>
							<CardDescription>{description}</CardDescription>
						</CardHeader>
						<CardContent className="px-4 py-4 md:px-10 md:py-8">{children}</CardContent>
					</Card>
				</div>
			</div>
		</div>
	);
}
