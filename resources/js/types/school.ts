export interface SchoolBranding {
    name: string;
    short_name: string;
    app_name: string;
    logo_url: string;
    favicon_url: string;
}

export interface ThemeColors {
    primary: string;
    secondary: string;
    sidebar: string;
    navbar: string;
}

export interface ThemeShare {
    name: string;
    colors: ThemeColors;
}
