<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\StudentInvoice;
use App\Models\StudentPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_module_is_restricted_to_admins(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'staf']))
            ->get(route('admin.finance.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.finance.index'))
            ->assertOk();
    }

    public function test_admin_can_create_update_and_delete_an_invoice_without_payments(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = Student::create(['nis' => 'FIN-001', 'nama' => 'Siswa Keuangan']);

        $this->actingAs($admin)
            ->post(route('admin.finance.invoices.store'), [
                'student_id' => $student->id,
                'category' => 'spp',
                'title' => 'SPP Oktober',
                'amount' => 100000,
                'due_date' => '2026-10-10',
            ])
            ->assertRedirect(route('admin.finance.index').'#invoices');

        $invoice = StudentInvoice::firstOrFail();
        $this->assertSame('SPP Oktober', $invoice->title);
        $this->assertSame('spp', $invoice->category);

        $this->get(route('admin.finance.invoices.edit', $invoice))
            ->assertOk()
            ->assertSee('Ubah Tagihan');

        $this->put(route('admin.finance.invoices.update', $invoice), [
            'category' => 'spp',
            'title' => 'SPP November',
            'amount' => 125000,
            'due_date' => '2026-11-10',
        ])->assertRedirect(route('admin.finance.index'));

        $this->assertDatabaseHas('student_invoices', [
            'id' => $invoice->id,
            'title' => 'SPP November',
            'amount' => 125000,
        ]);

        $this->delete(route('admin.finance.invoices.destroy', $invoice))
            ->assertRedirect(route('admin.finance.index').'#invoices');

        $this->assertDatabaseMissing('student_invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('finance_deletion_logs', [
            'invoice_id' => $invoice->id,
            'reason' => 'Dihapus oleh admin.',
            'deleted_by' => $admin->id,
        ]);
    }

    public function test_invoice_with_payments_cannot_be_reduced_below_paid_amount_but_can_be_deleted_with_an_audit_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->createInvoice(100000);
        $this->createPayment($invoice, 60000);

        $this->actingAs($admin)
            ->put(route('admin.finance.invoices.update', $invoice), [
                'category' => 'spp',
                'title' => $invoice->title,
                'amount' => 50000,
            ])
            ->assertSessionHasErrors('amount');

        $this->delete(route('admin.finance.invoices.destroy', $invoice))
            ->assertRedirect(route('admin.finance.index').'#invoices');

        $this->assertDatabaseMissing('student_invoices', [
            'id' => $invoice->id,
        ]);
        $this->assertDatabaseMissing('student_payments', ['student_invoice_id' => $invoice->id]);
        $this->assertDatabaseHas('finance_deletion_logs', [
            'invoice_id' => $invoice->id,
            'paid_amount' => 60000,
            'payment_count' => 1,
            'reason' => 'Dihapus oleh admin.',
            'deleted_by' => $admin->id,
        ]);
    }

    public function test_payment_crud_enforces_remaining_balance_and_refreshes_invoice_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->createInvoice(100000);
        $this->actingAs($admin);

        $this->post(route('admin.finance.payments.store', $invoice), [
            'amount' => 60000,
            'paid_on' => '2026-10-01',
            'method' => 'Transfer',
            'reference' => 'TRX-001',
        ])->assertRedirect(route('admin.finance.index'));

        $payment = StudentPayment::firstOrFail();
        $this->assertSame('sebagian', $invoice->fresh()->status);
        $this->get(route('admin.finance.payments.edit', [$invoice, $payment]))
            ->assertOk()
            ->assertSee('Ubah Pembayaran');

        $this->from(route('admin.finance.index'))
            ->post(route('admin.finance.payments.store', $invoice), [
                'amount' => 50000,
                'paid_on' => '2026-10-02',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(1, $invoice->payments()->count());

        $this->put(route('admin.finance.payments.update', [$invoice, $payment]), [
            'amount' => 100000,
            'paid_on' => '2026-10-03',
            'method' => 'Tunai',
            'reference' => 'TRX-002',
        ])->assertRedirect(route('admin.finance.index'));

        $this->assertSame('lunas', $invoice->fresh()->status);
        $this->assertDatabaseHas('student_payments', [
            'id' => $payment->id,
            'amount' => 100000,
            'method' => 'Tunai',
            'reference' => 'TRX-002',
        ]);

        $this->delete(route('admin.finance.payments.destroy', [$invoice, $payment]))
            ->assertRedirect(route('admin.finance.index'));

        $this->assertSame('belum_lunas', $invoice->fresh()->status);
        $this->assertDatabaseMissing('student_payments', ['id' => $payment->id]);
    }

    public function test_fully_paid_invoice_cannot_open_or_accept_a_new_payment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $invoice = $this->createInvoice(100000);
        $this->createPayment($invoice, 100000);
        $invoice->refreshStatus();

        $this->actingAs($admin)
            ->get(route('admin.finance.payments.create', $invoice))
            ->assertRedirect(route('admin.finance.index'))
            ->assertSessionHas('success', 'Tagihan ini sudah lunas. Tidak perlu mencatat pembayaran lagi.');

        $this->post(route('admin.finance.payments.store', $invoice), [
            'amount' => 10000,
            'paid_on' => '2026-10-02',
        ])->assertSessionHasErrors('invoice');

        $this->assertSame(1, $invoice->payments()->count());
    }

    private function createInvoice(int $amount): StudentInvoice
    {
        $student = Student::create([
            'nis' => 'FIN-'.Student::count().'-'.fake()->unique()->numerify('####'),
            'nama' => 'Siswa Keuangan',
        ]);

        return StudentInvoice::create([
            'student_id' => $student->id,
            'category' => 'spp',
            'title' => 'SPP',
            'amount' => $amount,
            'status' => 'belum_lunas',
        ]);
    }

    private function createPayment(StudentInvoice $invoice, int $amount): StudentPayment
    {
        return $invoice->payments()->create([
            'amount' => $amount,
            'paid_on' => '2026-10-01',
        ]);
    }
}
