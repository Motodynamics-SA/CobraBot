import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

type PermissionAction = 'view' | 'create' | 'update' | 'delete';
type PermissionResource = 'users' /* | 'posts' | 'comments' etc */;
type Permission = `${PermissionAction}_${PermissionResource}`;

export interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string | null;
    updated_at: string | null;
    deleted_at: string | null;
    avatar: string | null;
    can: Record<Permission, boolean>;
}

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href?: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    children?: NavItem[];
    defaultOpen?: boolean;
}

export interface SharedData {
    appName: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    [key: string]: unknown;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    ziggy: Config & { location: string };
};

export enum RolesEnum {
    ADMINISTRATOR = 'admin',
    USER_MANAGER = 'user-manager',
    REGISTERED_USER = 'registered-user'
}
