export type UserRole = 'admin_kecamatan' | 'admin_kabupaten';

export type DistrictRef = {
    id: number;
    code: string;
    name: string;
};

export type AuthUser = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    role_label: string;
    is_kabupaten: boolean;
    must_change_password: boolean;
    district: DistrictRef | null;
};

export type Auth = {
    user: AuthUser | null;
};
