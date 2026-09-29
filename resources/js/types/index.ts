export type * from './auth';
export type * from './report';

import type { Auth } from './auth';

export type AppIdentity = {
    name: string;
    tagline: string;
    institution_name: string;
    regency_name: string;
};

export type Flash = {
    success: string | null;
    error: string | null;
};

export type SharedProps = {
    app: AppIdentity;
    auth: Auth;
    flash: Flash;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};
