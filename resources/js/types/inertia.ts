import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import type { AuthShare } from './auth';
import type { Capabilities } from './navigation';
import type { NotificationsShare } from './notifications';
import type { SchoolBranding, ThemeShare } from './school';

export interface FlashShare {
    success: string | null;
    error: string | null;
    warning: string | null;
}

export interface AppPageProps extends InertiaPageProps {
    auth: AuthShare;
    flash: FlashShare;
    school: SchoolBranding;
    theme: ThemeShare;
    capabilities: Capabilities;
    notifications: NotificationsShare;
}
