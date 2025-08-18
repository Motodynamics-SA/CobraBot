import { useForm } from '@inertiajs/react';
import { useRef } from 'react';
import { useTranslations } from '@/hooks/use-translations';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import HeadingSmall from '@/components/heading-small';

import {
	Dialog,
	DialogClose,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogTitle,
	DialogTrigger,
} from '@/components/ui/dialog';

export default function DeleteUser() {
	const { t } = useTranslations();
	const passwordInput = useRef<HTMLInputElement>(null);
	const form = useForm<Required<{ password: string }>>({ password: '' });
	const { data, setData, processing, errors } = form;
	const destroy = (...args: Parameters<typeof form.delete>) => form.delete(...args);
	const reset = (...args: Parameters<typeof form.reset>) => form.reset(...args);
	const clearErrors = (...args: Parameters<typeof form.clearErrors>) => form.clearErrors(...args);

	const deleteUser = (e: React.FormEvent<HTMLFormElement>) => {
		e.preventDefault();

		destroy(route('profile.destroy'), {
			preserveScroll: true,
			onSuccess: () => closeModal(),
			onError: () => passwordInput.current?.focus(),
			onFinish: () => reset(),
		});
	};

	const closeModal = () => {
		clearErrors();
		reset();
	};

	return (
		<div className="space-y-6">
			<HeadingSmall
				title={t('settings.profile.delete_account.title')}
				description={t('settings.profile.delete_account.description')}
			/>
			<div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
				<div className="relative space-y-0.5 text-red-600 dark:text-red-100">
					<p className="font-medium">{t('settings.profile.delete_account.warning')}</p>
					<p className="text-sm">
						{t('settings.profile.delete_account.warning_description')}
					</p>
				</div>

				<Dialog>
					<DialogTrigger asChild>
						<Button variant="destructive">
							{t('settings.profile.delete_account.button')}
						</Button>
					</DialogTrigger>
					<DialogContent>
						<DialogTitle>
							{t('settings.profile.delete_account.confirm_title')}
						</DialogTitle>
						<DialogDescription>
							{t('settings.profile.delete_account.confirm_description')}
						</DialogDescription>
						<form className="space-y-6" onSubmit={deleteUser}>
							<div className="grid gap-2">
								<Label htmlFor="password" className="sr-only">
									{t('settings.profile.delete_account.password')}
								</Label>

								<Input
									id="password"
									type="password"
									name="password"
									ref={passwordInput}
									value={data.password}
									onChange={(e) => setData('password', e.target.value)}
									placeholder={t(
										'settings.profile.delete_account.password_placeholder'
									)}
									autoComplete="current-password"
								/>

								<InputError message={errors.password} />
							</div>

							<DialogFooter className="gap-2">
								<DialogClose asChild>
									<Button variant="secondary" onClick={closeModal}>
										{t('settings.profile.delete_account.cancel')}
									</Button>
								</DialogClose>

								<Button variant="destructive" disabled={processing} asChild>
									<button type="submit">
										{t('settings.profile.delete_account.confirm_button')}
									</button>
								</Button>
							</DialogFooter>
						</form>
					</DialogContent>
				</Dialog>
			</div>
		</div>
	);
}
