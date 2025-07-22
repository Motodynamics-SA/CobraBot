import AppLayout from '@/layouts/app-layout';
import { PageProps, User, Auth } from '@/types/index.d';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
	Table,
	TableBody,
	TableCell,
	TableHead,
	TableHeader,
	TableRow,
} from '@/components/ui/table';
import { Plus, Pencil, Trash, MoreHorizontal, Eye, ArrowUpCircle } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';
import { usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useState } from 'react';
import { DeleteUserModal } from './components/delete-user-modal';
import { RestoreUserModal } from './components/restore-user-modal';
import { cn } from '@/lib/utils';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/react';
import { useDebouncedCallback } from 'use-debounce';
import { formatDate } from '@/utils/format';
interface UsersIndexProps extends PageProps {
	users: User[];
	filters: {
		search: string;
	};
}

export default function UsersIndex({ users, filters }: UsersIndexProps) {
	const { t } = useTranslations();
	const { auth } = usePage().props as unknown as { auth: Auth };
	const [userToDelete, setUserToDelete] = useState<User | null>(null);
	const [userToRestore, setUserToRestore] = useState<User | null>(null);

	const { data, setData, get } = useForm<{ search: string }>({
		search: filters.search ?? '',
	});

	const debouncedSearch = useDebouncedCallback<(value: string) => void>((value: string) => {
		void get(
			route('users.index', {
				search: value || undefined,
			} as const),
			{
				preserveState: true,
				preserveScroll: true,
			}
		);
	}, 300);

	const canUpdateUser = (user: User) => {
		return (
			auth.user?.can.update_users &&
			(!user.role || user.role !== 'admin' || auth.user.role === 'admin')
		);
	};

	const canDeleteUser = (user: User) => {
		return (
			auth.user?.can.delete_users &&
			auth.user.id !== user.id &&
			(!user.role || user.role !== 'admin' || auth.user.role === 'admin')
		);
	};

	return (
		<AppLayout
			breadcrumbs={[
				{
					title: t('users.title'),
					href: route('users.index'),
				},
			]}
		>
			<Head title={t('users.index_page_title')} />
			<div className="p-6">
				<div className="mb-6 flex items-center justify-between">
					<h1 className="text-2xl font-semibold">{t('users.title')}</h1>
					{auth.user?.can.create_users && (
						<Button asChild>
							<Link href={route('users.create')}>
								<Plus className="mr-2 h-4 w-4" />
								{t('users.actions.new_big_button')}
							</Link>
						</Button>
					)}
				</div>

				<div className="mb-4">
					<Input
						type="search"
						placeholder={t('users.placeholders.search')}
						value={data.search}
						onChange={(e) => {
							setData('search', e.target.value);
							debouncedSearch(e.target.value);
						}}
						className="max-w-sm"
					/>
				</div>

				<Table>
					<TableHeader>
						<TableRow>
							<TableHead>{t('users.labels.name')}</TableHead>
							<TableHead>{t('users.labels.email')}</TableHead>
							<TableHead>{t('users.labels.role')}</TableHead>
							<TableHead>{t('users.labels.created_at')}</TableHead>
							<TableHead>{t('users.labels.status')}</TableHead>
							{(auth.user?.can.update_users || auth.user?.can.delete_users) && (
								<TableHead className="w-[100px]">
									{t('users.labels.actions')}
								</TableHead>
							)}
						</TableRow>
					</TableHeader>
					<TableBody>
						{users.map((user) => (
							<TableRow key={user.id}>
								<TableCell>{user.name}</TableCell>
								<TableCell>{user.email}</TableCell>
								<TableCell>{t(`roles.${user.role}`)}</TableCell>
								<TableCell>{formatDate(user.created_at)}</TableCell>
								<TableCell>
									<span
										className={cn(
											'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
											user.deleted_at
												? 'bg-red-50 text-red-700'
												: 'bg-green-50 text-green-700'
										)}
									>
										{user.deleted_at
											? t('users.status.inactive')
											: t('users.status.active')}
									</span>
								</TableCell>
								{(auth.user?.can.update_users || auth.user?.can.delete_users) && (
									<TableCell>
										<DropdownMenu>
											<DropdownMenuTrigger asChild>
												<Button variant="ghost" size="icon">
													<MoreHorizontal className="h-4 w-4" />
													<span className="sr-only">
														{t('common.actions')}
													</span>
												</Button>
											</DropdownMenuTrigger>
											<DropdownMenuContent align="end">
												<DropdownMenuItem asChild>
													<Link href={route('users.show', user.id)}>
														<Eye className="mr-2 h-4 w-4" />
														{t('users.actions.show')}
													</Link>
												</DropdownMenuItem>
												{canUpdateUser(user) && (
													<DropdownMenuItem asChild>
														<Link href={route('users.edit', user.id)}>
															<Pencil className="mr-2 h-4 w-4" />
															{t('users.actions.edit')}
														</Link>
													</DropdownMenuItem>
												)}
												{canDeleteUser(user) && !user.deleted_at && (
													<DropdownMenuItem
														onSelect={() => setUserToDelete(user)}
													>
														<Trash className="mr-2 h-4 w-4" />
														{t('users.actions.delete')}
													</DropdownMenuItem>
												)}
												{canUpdateUser(user) && user.deleted_at && (
													<DropdownMenuItem
														onSelect={() => setUserToRestore(user)}
													>
														<ArrowUpCircle className="mr-2 h-4 w-4" />
														{t('users.actions.restore')}
													</DropdownMenuItem>
												)}
											</DropdownMenuContent>
										</DropdownMenu>
									</TableCell>
								)}
							</TableRow>
						))}
					</TableBody>
				</Table>
			</div>

			<DeleteUserModal
				user={userToDelete}
				open={userToDelete !== null}
				onClose={() => setUserToDelete(null)}
			/>
			<RestoreUserModal
				user={userToRestore}
				open={userToRestore !== null}
				onClose={() => setUserToRestore(null)}
			/>
		</AppLayout>
	);
}
