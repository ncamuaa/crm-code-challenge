import { Component, OnInit } from '@angular/core';
import { Subject } from 'rxjs';
import { debounceTime, distinctUntilChanged, switchMap } from 'rxjs/operators';
import { Customer } from '../../core/models/customer.model';
import { CustomerService } from '../../core/services/customer.service';

@Component({
  selector: 'app-customer-list',
  templateUrl: './customer-list.component.html',
})
export class CustomerListComponent implements OnInit {
  customers: Customer[] = [];
  total = 0;
  page = 1;
  perPage = 10;
  query = '';
  loading = false;
  errorMessage = '';
  private searchTerms = new Subject<string>();

  constructor(private customerService: CustomerService) {}

  ngOnInit(): void {
    this.searchTerms
      .pipe(
        debounceTime(300),
        distinctUntilChanged(),
        switchMap((term) => {
          this.loading = true;
          this.page = 1;
          return this.customerService.list(this.page, this.perPage, term);
        })
      )
      .subscribe({
        next: (res) => {
          this.customers = res.data;
          this.total = res.meta.total;
          this.loading = false;
        },
        error: () => {
          this.errorMessage = 'Failed to search customers.';
          this.loading = false;
        },
      });

    this.fetchPage();
  }

  onSearchChange(term: string): void {
    this.query = term;
    this.searchTerms.next(term);
  }

  fetchPage(): void {
    this.loading = true;
    this.errorMessage = '';

    this.customerService.list(this.page, this.perPage, this.query).subscribe({
      next: (res) => {
        this.customers = res.data;
        this.total = res.meta.total;
        this.loading = false;
      },
      error: () => {
        this.errorMessage = 'Failed to load customers. Is the API reachable?';
        this.loading = false;
      },
    });
  }

  nextPage(): void {
    if (this.page * this.perPage >= this.total) {
      return;
    }
    this.page += 1;
    this.fetchPage();
  }

  previousPage(): void {
    if (this.page <= 1) {
      return;
    }
    this.page -= 1;
    this.fetchPage();
  }

  deleteCustomer(customer: Customer): void {
    if (!customer.id) {
      return;
    }
    if (!confirm(`Delete ${customer.first_name} ${customer.last_name}?`)) {
      return;
    }

    this.customerService.delete(customer.id).subscribe({
      next: () => this.fetchPage(),
      error: () => (this.errorMessage = 'Failed to delete customer.'),
    });
  }
}
