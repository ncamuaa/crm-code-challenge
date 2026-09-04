export interface Customer {
  id?: string;
  first_name: string;
  last_name: string;
  email: string;
  contact_number: string;
  created_at?: string;
  updated_at?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    total: number;
    page: number;
    per_page: number;
    source: 'database' | 'search';
  };
}
