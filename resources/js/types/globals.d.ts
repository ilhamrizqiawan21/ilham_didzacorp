export {};

declare global {
    interface Window {
        confirmAction?: (
            message: string,
            callback: (confirmed: boolean) => void,
            options?: { title?: string; confirmText?: string; cancelText?: string; danger?: boolean },
        ) => void;
        confirmDialog?: (message: string, config?: {
            title?: string;
            confirmText?: string;
            cancelText?: string;
            danger?: boolean;
        }) => Promise<boolean>;
        showToast?: (message: string, type?: 'success' | 'error' | 'warning' | 'info') => void;
    }
}
