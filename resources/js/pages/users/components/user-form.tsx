import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { useTranslations } from '@/hooks/use-translations';

interface UserFormProps {
	user?: {
		id: number;
		name: string;
		email: string;
		role?: string;
	};
	action: string;
	roles: {
		name: string;
		label: string;
	}[];
}

export function UserForm({ user, action, roles }: UserFormProps) {
	const { t } = useTranslations();
	const { data, setData, post, put, processing, errors } = useForm({
		name: user?.name ?? '',
		email: user?.email ?? '',
		password: '',
		password_confirmation: '',
		role: user?.role ?? (roles.length === 1 ? roles[0].name : ''),
	});

	const handleSubmit: FormEventHandler = (e) => {
		e.preventDefault();
		const options = {
			preserveState: true,
			preserveScroll: true,
			preserveSearchParams: true,
		};

		if (user) {
			put(action, options);
		} else {
			post(action, options);
		}
	};

	return (
		<form onSubmit={handleSubmit} className="space-y-4">
			<div>
				<Label htmlFor="name">{t('users.labels.name')}</Label>
				<Input
					id="name"
					value={data.name}
					onChange={(e) => setData('name', e.target.value)}
					autoComplete="name"
				/>
				{errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
			</div>

			<div>
				<Label htmlFor="email">{t('users.labels.email')}</Label>
				<Input
					id="email"
					type="email"
					value={data.email}
					onChange={(e) => setData('email', e.target.value)}
					autoComplete="email"
				/>
				{errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
			</div>

			<div>
				<Label htmlFor="role">{t('users.labels.role')}</Label>
				{roles.length > 1 ? (
					<Select value={data.role} onValueChange={(value) => setData('role', value)}>
						<SelectTrigger id="role">
							<SelectValue placeholder={t('users.placeholders.select_role')} />
						</SelectTrigger>
						<SelectContent>
							{roles.map((role) => (
								<SelectItem key={role.name} value={role.name}>
									{t(role.label)}
								</SelectItem>
							))}
						</SelectContent>
					</Select>
				) : (
					<Input id="role" type="text" value={t(roles[0].label)} disabled />
				)}
				{errors.role && <p className="mt-1 text-sm text-red-600">{errors.role}</p>}
			</div>

			<div>
				<Label htmlFor="password">{t('users.labels.password')}</Label>
				<Input
					id="password"
					type="password"
					value={data.password}
					onChange={(e) => setData('password', e.target.value)}
					autoComplete="new-password"
				/>
				{errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
			</div>

			<div>
				<Label htmlFor="password_confirmation">
					{t('users.labels.password_confirmation')}
				</Label>
				<Input
					id="password_confirmation"
					type="password"
					value={data.password_confirmation}
					onChange={(e) => setData('password_confirmation', e.target.value)}
					autoComplete="new-password"
				/>
			</div>

			<Button type="submit" disabled={processing}>
				{user ? t('users.actions.update') : t('users.actions.create')}
			</Button>
		</form>
	);
}
