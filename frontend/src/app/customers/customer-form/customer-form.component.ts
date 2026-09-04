import { Component, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { CustomerService } from '../../core/services/customer.service';

@Component({
  selector: 'app-customer-form',
  templateUrl: './customer-form.component.html',
})
export class CustomerFormComponent implements OnInit {
  form: FormGroup;
  customerId: string | null = null;
  isEditMode = false;
  submitting = false;
  errorMessage = '';
  fieldErrors: Record<string, string[]> = {};

  constructor(
    private fb: FormBuilder,
    private customerService: CustomerService,
    private route: ActivatedRoute,
    private router: Router
  ) {
    this.form = this.fb.group({
      first_name: ['', [Validators.required, Validators.maxLength(255)]],
      last_name: ['', [Validators.required, Validators.maxLength(255)]],
      email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
      contact_number: ['', [Validators.required, Validators.maxLength(50)]],
    });
  }

  ngOnInit(): void {
    this.customerId = this.route.snapshot.paramMap.get('id');
    this.isEditMode = !!this.customerId;

    if (this.isEditMode && this.customerId) {
      this.customerService.get(this.customerId).subscribe({
        next: (res) => this.form.patchValue(res.data),
        error: () => (this.errorMessage = 'Failed to load customer.'),
      });
    }
  }

  submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting = true;
    this.errorMessage = '';
    this.fieldErrors = {};

    const request = this.isEditMode && this.customerId
      ? this.customerService.update(this.customerId, this.form.value)
      : this.customerService.create(this.form.value);

    request.subscribe({
      next: () => this.router.navigate(['/customers']),
      error: (err) => {
        this.submitting = false;
        if (err.status === 422) {
          this.fieldErrors = err.error ?? {};
        } else {
          this.errorMessage = 'Something went wrong. Please try again.';
        }
      },
    });
  }

  cancel(): void {
    this.router.navigate(['/customers']);
  }
}
