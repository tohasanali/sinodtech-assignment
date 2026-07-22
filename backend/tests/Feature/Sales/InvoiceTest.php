<?php

namespace Tests\Feature\Sales;

use App\Mail\SaleInvoiceMail;
use App\Models\Branch;
use App\Models\BranchStock;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sale_linked_to_a_customer_sends_an_invoice_email_with_a_pdf_attachment(): void
    {
        Mail::fake();

        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 10]);
        $customer = Customer::factory()->create();
        $user = User::factory()->create();

        app(SaleService::class)->process($branch, $customer, $user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        Mail::assertSent(SaleInvoiceMail::class, function (SaleInvoiceMail $mail) use ($customer) {
            return $mail->hasTo($customer->email) && count($mail->attachments()) === 1;
        });
    }

    public function test_a_walk_in_sale_sends_no_invoice_email(): void
    {
        Mail::fake();

        $branch = Branch::factory()->create();
        $product = Product::factory()->create();
        BranchStock::factory()->create(['branch_id' => $branch->id, 'product_id' => $product->id, 'quantity' => 10]);
        $user = User::factory()->create();

        app(SaleService::class)->process($branch, null, $user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ]);

        Mail::assertNothingSent();
    }
}
