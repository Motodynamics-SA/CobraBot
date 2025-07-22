import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { User } from '@/types';
import { useTranslations } from '@/hooks/use-translations';
import { router } from '@inertiajs/react';
import { useState } from 'react';

interface DeleteUserModalProps {
	user: User | null;
	open: boolean;
	onClose: () => void;
}

export function DeleteUserModal({ user, open, onClose }: DeleteUserModalProps) {
	const [isDeleting, setIsDeleting] = useState(false);
	const { t } = useTranslations();

	if (!user) return null;

	const handleDelete = () => {
		setIsDeleting(true);
		router.delete(route('users.destroy', user.id), {
			preserveScroll: true,
			preserveState: true,
			onSuccess: () => {
				onClose();
			},
			onError: () => {
				setIsDeleting(false);
			},
			onFinish: () => {
				setIsDeleting(false);
			},
		});
	};

	return (
		<Dialog open={open} onOpenChange={onClose}>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>{t('users.delete.title')}</DialogTitle>
					<DialogDescription>{t('users.delete.description')}</DialogDescription>
				</DialogHeader>
				<div className="space-y-4">
					<div>
						<p className="text-muted-foreground text-sm font-medium">
							{t('users.labels.name')}
						</p>
						<p>{user.name}</p>
					</div>
					<div>
						<p className="text-muted-foreground text-sm font-medium">
							{t('users.labels.email')}
						</p>
						<p>{user.email}</p>
					</div>
				</div>
				<DialogFooter>
					<Button variant="outline" onClick={onClose}>
						{t('common.cancel')}
					</Button>
					<Button variant="destructive" onClick={handleDelete} disabled={isDeleting}>
						{t('users.actions.delete')}
					</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	);
}
