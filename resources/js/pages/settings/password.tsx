import InputError from '@/components/input-error';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm } from '@inertiajs/react';
import { useRef } from 'react';
import { useTranslations } from '@/hooks/use-translations';

import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Password() {
	const { t } = useTranslations();

	const breadcrumbs: BreadcrumbItem[] = [
		{
			title: t('settings.password.title'),
			href: '/settings/password',
		},
	];
	const passwordInput = useRef<HTMLInputElement>(null);
	const currentPasswordInput = useRef<HTMLInputElement>(null);

	const form = useForm({
		current_password: '',
		password: '',
		password_confirmation: '',
	});
	const { data, setData, processing, errors } = form;
	const put = (...args: Parameters<typeof form.put>) => form.put(...args);
	const reset = (...args: Parameters<typeof form.reset>) => form.reset(...args);
	const recentlySuccessful = form.recentlySuccessful || false;

	const updatePassword = (e: React.FormEvent<HTMLFormElement>) => {
		e.preventDefault();

		put(route('password.update'), {
			preserveScroll: true,
			onSuccess: () => reset(),
			onError: (errors) => {
				if (errors.password) {
					reset('password', 'password_confirmation');
					passwordInput.current?.focus();
				}

				if (errors.current_password) {
					reset('current_password');
					currentPasswordInput.current?.focus();
				}
			},
		});
	};

	return (
		<AppLayout breadcrumbs={breadcrumbs}>
			<Head title={t('settings.password.title')} />

			<SettingsLayout>
				<div className="space-y-6">
					<HeadingSmall
						title={t('settings.password.title')}
						description={t('settings.password.description')}
					/>

					<form onSubmit={updatePassword} className="space-y-6">
						<div className="grid gap-2">
							<Label htmlFor="current_password">
								{t('settings.password.current_password')}
							</Label>

							<Input
								id="current_password"
								ref={currentPasswordInput}
								value={data.current_password}
								onChange={(e) => setData('current_password', e.target.value)}
								type="password"
								className="mt-1 block w-full"
								autoComplete="current-password"
								placeholder={t('settings.password.current_password_placeholder')}
							/>

							<InputError message={errors.current_password} />
						</div>

						<div className="grid gap-2">
							<Label htmlFor="password">{t('settings.password.new_password')}</Label>

							<Input
								id="password"
								ref={passwordInput}
								value={data.password}
								onChange={(e) => setData('password', e.target.value)}
								type="password"
								className="mt-1 block w-full"
								autoComplete="new-password"
								placeholder={t('settings.password.new_password_placeholder')}
							/>

							<InputError message={errors.password} />
						</div>

						<div className="grid gap-2">
							<Label htmlFor="password_confirmation">
								{t('settings.password.confirm_password')}
							</Label>

							<Input
								id="password_confirmation"
								value={data.password_confirmation}
								onChange={(e) => setData('password_confirmation', e.target.value)}
								type="password"
								className="mt-1 block w-full"
								autoComplete="new-password"
								placeholder={t('settings.password.confirm_password_placeholder')}
							/>

							<InputError message={errors.password_confirmation} />
						</div>

						<div className="flex items-center gap-4">
							<Button disabled={processing}>
								{t('settings.password.save_button')}
							</Button>

							<Transition
								show={recentlySuccessful}
								enter="transition ease-in-out"
								enterFrom="opacity-0"
								leave="transition ease-in-out"
								leaveTo="opacity-0"
							>
								<p className="text-sm text-neutral-600">
									{t('settings.password.saved')}
								</p>
							</Transition>
						</div>
					</form>
				</div>
			</SettingsLayout>
		</AppLayout>
	);
}
