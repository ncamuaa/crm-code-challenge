import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Customer, PaginatedResponse } from '../models/customer.model';

@Injectable({ providedIn: 'root' })
export class CustomerService {
  private readonly baseUrl = `${environment.apiBaseUrl}/customers`;

  constructor(private http: HttpClient) {}

  list(page = 1, perPage = 15, query = ''): Observable<PaginatedResponse<Customer>> {
    let params = new HttpParams()
      .set('page', page)
      .set('per_page', perPage);

    if (query.trim()) {
      params = params.set('q', query.trim());
    }

    return this.http.get<PaginatedResponse<Customer>>(this.baseUrl, { params });
  }

  get(id: string): Observable<{ data: Customer }> {
    return this.http.get<{ data: Customer }>(`${this.baseUrl}/${id}`);
  }

  create(customer: Customer): Observable<{ data: Customer }> {
    return this.http.post<{ data: Customer }>(this.baseUrl, customer);
  }

  update(id: string, customer: Partial<Customer>): Observable<{ data: Customer }> {
    return this.http.put<{ data: Customer }>(`${this.baseUrl}/${id}`, customer);
  }

  delete(id: string): Observable<void> {
    return this.http.delete<void>(`${this.baseUrl}/${id}`);
  }
}
