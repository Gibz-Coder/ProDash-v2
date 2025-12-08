import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
    permissions?: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | string;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    emp_no: string;
    emp_name: string;
    role: string;
    position?: string;
    title_class?: string;
    rank?: string;
    hr_job_name?: string;
    job_assigned?: string;
    avatar?: string;
    emp_verified_at: string | null;
    created_at: string;
    updated_at: string;
    // Legacy fields for backward compatibility
    name?: string;
    email?: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
