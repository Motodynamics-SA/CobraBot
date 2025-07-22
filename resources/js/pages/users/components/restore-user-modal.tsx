import { FormEvent } from 'react';
import { User } from '@/types';
import { useTranslations } from '@/hooks/use-translations';
import { Button } from '@/components/ui/button';
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';

interface RestoreUserModalProps {
	user: User | null;
	open: boolean;
	onClose: () => void;
}

export function RestoreUserModal({ user, open, onClose }: RestoreUserModalProps) {
	const { t } = useTranslations();
	const form = useForm({});

	const handleSubmit = (e: FormEvent) => {
		e.preventDefault();
		form.put(route('users.restore', user?.id), {
			onSuccess: () => {
				onClose();
			},
		});
	};

	return (
		<Dialog open={open} onOpenChange={onClose}>
			<DialogContent>
				<DialogHeader>
					<DialogTitle>{t('users.restore.title')}</DialogTitle>
					<DialogDescription>
						{t('users.restore.description') + ': ' + user?.name}
					</DialogDescription>
				</DialogHeader>
				<DialogFooter>
					<Button variant="outline" onClick={onClose} disabled={form.processing}>
						{t('common.cancel')}
					</Button>
					<Button variant="default" onClick={handleSubmit} disabled={form.processing}>
						{t('users.actions.restore')}
					</Button>
				</DialogFooter>
			</DialogContent>
		</Dialog>
	);
}
