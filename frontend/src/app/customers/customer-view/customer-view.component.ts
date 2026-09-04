import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { Customer } from '../../core/models/customer.model';
import { CustomerService } from '../../core/services/customer.service';

@Component({
  selector: 'app-customer-view',
  templateUrl: './customer-view.component.html',
})
export class CustomerViewComponent implements OnInit {
  customer: Customer | null = null;
  errorMessage = '';

  constructor(private route: ActivatedRoute, private customerService: CustomerService) {}

  ngOnInit(): void {
    const id = this.route.snapshot.paramMap.get('id');
    if (!id) {
      return;
    }

    this.customerService.get(id).subscribe({
      next: (res) => (this.customer = res.data),
      error: () => (this.errorMessage = 'Customer not found.'),
    });
  }
}
