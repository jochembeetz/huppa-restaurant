import { type User } from '@/types/user';

export interface PageProps {
    auth: {
        user: User;
    };
}

export interface Order {
    id: number;
    user_id: number;
    status: 'pending' | 'processing' | 'completed' | 'cancelled';
    total_amount: number;
    notes: string | null;
    created_at: string;
    updated_at: string;
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}
