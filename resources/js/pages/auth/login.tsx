import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useEffect, useState, useCallback, useRef } from 'react';
import { usePage } from '@inertiajs/react';
import 'altcha';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { useTranslations } from '@/hooks/use-translations';

type LoginForm = {
	email: string;
	password: string;
	remember: boolean;
	captcha?: string;
};

interface LoginProps {
	status?: string;
	canResetPassword: boolean;
	token?: string;
	redirectTo?: string;
}

export default function Login({ status, canResetPassword, token, redirectTo }: LoginProps) {
	const { data, setData, post, processing, errors, reset } = useForm<Required<LoginForm>>({
		email: '',
		password: '',
		remember: false,
		captcha: '',
	});

	const { errors: pageErrors } = usePage().props as {
		errors: { email?: string; login?: string; captcha?: string };
	};
	const { t } = useTranslations();
	const [altchaError, setAltchaError] = useState<string>('');
	const altchaRef = useRef<HTMLElement>(null);

	// Handler for Altcha state change
	const handleAltchaStateChange = useCallback(
		(event: CustomEvent<{ payload: string; state: string }>) => {
			if (event.detail?.state === 'verified') {
				const token = event.detail?.payload || '';
				setData('captcha', token);
				if (token) setAltchaError('');
			}
		},
		[setData, setAltchaError]
	);

	useEffect(() => {
		const node = altchaRef.current;
		if (node) {
			node.addEventListener('statechange', handleAltchaStateChange as EventListener);
		}
	}, [handleAltchaStateChange]);

	useEffect(() => {
		if (token && redirectTo) {
			// Store token in localStorage
			localStorage.setItem('auth_token', token);
			// remove the previous url from the history
			window.history.replaceState({}, document.title);
			// Redirect to the specified route
			window.location.replace(redirectTo);
		}
	}, [token, redirectTo]);

	useEffect(() => {
		// Only run on mount
		fetch('/api/v1/user/info', {
			credentials: 'same-origin',
			headers: {
				Accept: 'application/json',
			},
		})
			.then((res) => {
				if (res.ok) {
					// User is authenticated, force reload so backend can redirect
					window.location.reload();
				}
			})
			.catch(() => {
				// Network error, do nothing
			});
	}, []);

	const submit: FormEventHandler = (e) => {
		e.preventDefault();
		if (!data.captcha) {
			setAltchaError(t('auth.login.captcha'));
			return;
		}
		post(window.location.pathname + window.location.search, {
			onFinish: () => {
				reset('password');
			},
		});
	};

	return (
		<AuthLayout title={t('auth.login.title')} description="">
			<Head title="Log in" />

			<div className="mx-auto flex w-full max-w-5xl flex-col items-stretch justify-center gap-8 md:flex-row">
				{/* Left: Log in with your account */}
				<section className="flex flex-1 flex-col justify-center rounded-lg border border-gray-200 bg-white p-6 shadow">
					<form className="flex flex-col gap-6" onSubmit={submit}>
						{pageErrors.login && (
							<div className="bg-destructive/15 rounded-md p-4">
								<div className="flex">
									<div className="flex-shrink-0">
										<svg
											className="text-destructive h-5 w-5"
											viewBox="0 0 20 20"
											fill="currentColor"
										>
											<path
												fillRule="evenodd"
												d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
												clipRule="evenodd"
											/>
										</svg>
									</div>
									<div className="ml-3">
										<p className="text-destructive text-sm">
											{pageErrors.login}
										</p>
									</div>
								</div>
							</div>
						)}

						<div className="grid gap-6">
							<div className="grid gap-2">
								<Label htmlFor="email">{t('auth.login.email')}</Label>
								<Input
									id="email"
									type="email"
									required
									autoFocus
									tabIndex={1}
									autoComplete="email"
									value={data.email}
									onChange={(e) => setData('email', e.target.value)}
									placeholder="email@example.com"
								/>
								<InputError message={pageErrors.email} />
							</div>

							<div className="grid gap-2">
								<div className="flex items-center">
									<Label htmlFor="password">{t('auth.login.password')}</Label>
								</div>
								<Input
									id="password"
									type="password"
									required
									tabIndex={2}
									autoComplete="current-password"
									value={data.password}
									onChange={(e) => setData('password', e.target.value)}
									placeholder={t('auth.login.password')}
								/>
								<InputError message={errors.password} />
								<div className="mb-2 flex flex-col items-start justify-start gap-3">
									{canResetPassword && (
										<TextLink
											href={route('password.request')}
											className="ml-0 text-sm"
											tabIndex={5}
										>
											{t('auth.login.forgot_password')}
										</TextLink>
									)}
								</div>
							</div>

							<div className="flex items-center space-x-3">
								<Checkbox
									id="remember"
									name="remember"
									checked={data.remember}
									onClick={() => setData('remember', !data.remember)}
									tabIndex={3}
								/>
								<Label htmlFor="remember">{t('auth.login.remember')}</Label>
							</div>

							<div className="mb-1 mt-2 flex justify-start">
								<altcha-widget
									id="altcha-widget"
									hidelogo
									hidefooter
									challengeurl="/altcha-challenge"
									ref={altchaRef}
								/>
							</div>
							{pageErrors.captcha && <InputError message={pageErrors.captcha} />}
							{altchaError && <InputError message={altchaError} />}
							<Button
								type="submit"
								className="mt-4 w-full py-4"
								tabIndex={4}
								disabled={processing}
							>
								{processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
								{t('auth.login.button')}
							</Button>
						</div>
					</form>
				</section>
			</div>

			{status && (
				<div className="mb-1 text-center text-sm font-medium text-green-600">{status}</div>
			)}
		</AuthLayout>
	);
}
