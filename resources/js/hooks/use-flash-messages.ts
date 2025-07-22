import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

interface Flash {
	success?: string;
	error?: string;
	warning?: string;
	info?: string;
}

export function useFlashMessages() {
	const { flash } = usePage().props as unknown as { flash: Flash };

	useEffect(() => {
		if (flash.success) {
			toast.success(flash.success);
		}
		if (flash.error) {
			toast.error(flash.error);
		}
		if (flash.warning) {
			toast.warning(flash.warning);
		}
		if (flash.info) {
			toast.info(flash.info);
		}
	}, [flash]);
}
