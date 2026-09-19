import type { UserRole } from './auth';

export interface SidebarSection {
    type: 'section';
    label: string;
}

export interface SidebarItem {
    type: 'item';
    label: string;
    href: string;
    icon: string;
    activePrefixes: string[];
    inertia?: boolean;
    hint?: string;
}

export type SidebarMenuEntry = SidebarSection | SidebarItem;

export interface Capabilities {
    has_wali_kelas: boolean;
    [key: string]: boolean;
}

export type SidebarMenuByRole = Partial<Record<UserRole, SidebarMenuEntry[]>>;
