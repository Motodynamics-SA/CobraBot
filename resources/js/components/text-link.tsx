import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import React from 'react';

interface TextLinkProps extends React.AnchorHTMLAttributes<HTMLAnchorElement> {
	href: string;
	children: React.ReactNode;
	className?: string;
}

export default function TextLink({ href, children, className, ...props }: TextLinkProps) {
	const isExternal = href.startsWith('http') || href.startsWith('//');

	const classes = cn('text-primary underline-offset-4 hover:underline', className);

	if (isExternal) {
		return (
			<a href={href} className={classes} target="_blank" rel="noopener noreferrer" {...props}>
				{children}
			</a>
		);
	}

	return (
		<Link href={href} className={classes} {...props}>
			{children}
		</Link>
	);
}
