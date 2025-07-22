import { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import Navbar from '@/components/navbar';

interface LandingLayoutProps {
	children: ReactNode;
	title?: string;
}

export default function LandingLayout({ children, title }: LandingLayoutProps) {
	return (
		<div className="bg-background min-h-screen">
			<Head title={title} />
			<Navbar />
			<main className="flex-1 pt-16">{children}</main>
		</div>
	);
}
