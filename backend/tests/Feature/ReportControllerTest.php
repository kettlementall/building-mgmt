<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payment;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_income_report_requires_year(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/income');

        $response->assertUnprocessable()->assertJsonValidationErrors(['year']);
    }

    public function test_income_report_returns_data_for_year(): void
    {
        $user = User::factory()->create();
        $bill = Bill::factory()->paid()->create(['year' => 2025, 'month' => 3]);
        Payment::factory()->create([
            'bill_id'     => $bill->id,
            'amount'      => 2000,
            'paid_at'     => '2025-03-10',
            'recorded_by' => $user->id,
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/income?year=2025');

        $response->assertOk();
        $data = $response->json();
        $this->assertNotEmpty($data);
        $this->assertEquals(2025, $data[0]['year']);
        $this->assertEquals(3, $data[0]['month']);
        $this->assertEquals(2000, $data[0]['total']);
    }

    public function test_income_report_can_filter_by_month(): void
    {
        $user = User::factory()->create();
        $bill1 = Bill::factory()->paid()->create(['year' => 2025, 'month' => 3]);
        $bill2 = Bill::factory()->paid()->create(['year' => 2025, 'month' => 6]);

        Payment::factory()->create(['bill_id' => $bill1->id, 'paid_at' => '2025-03-10', 'recorded_by' => $user->id]);
        Payment::factory()->create(['bill_id' => $bill2->id, 'paid_at' => '2025-06-10', 'recorded_by' => $user->id]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/income?year=2025&month=3');

        $response->assertOk();
        $this->assertCount(1, $response->json());
    }

    public function test_expense_report_requires_year(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/expense');

        $response->assertUnprocessable()->assertJsonValidationErrors(['year']);
    }

    public function test_expense_report_returns_by_category(): void
    {
        $user = User::factory()->create();
        $cat  = ExpenseCategory::factory()->create(['name' => '清潔費']);
        Expense::factory()->count(2)->create([
            'category_id'  => $cat->id,
            'amount'       => 3000,
            'expense_date' => '2025-04-01',
            'recorded_by'  => $user->id,
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/expense?year=2025');

        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('by_category', $data);
        $this->assertArrayHasKey('total', $data);
        $this->assertEquals(6000, $data['total']);
        $this->assertEquals('清潔費', $data['by_category'][0]['category']);
    }

    public function test_balance_report_requires_year(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/balance');

        $response->assertUnprocessable()->assertJsonValidationErrors(['year']);
    }

    public function test_balance_report_returns_12_months(): void
    {
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/balance?year=2025');

        $response->assertOk();
        $data = $response->json();
        $this->assertArrayHasKey('months', $data);
        $this->assertCount(12, $data['months']);
    }

    public function test_balance_report_calculates_correct_balance(): void
    {
        $user = User::factory()->create();
        $cat  = ExpenseCategory::factory()->create();

        // 收入
        $bill = Bill::factory()->paid()->create(['year' => 2025, 'month' => 5]);
        Payment::factory()->create([
            'bill_id'     => $bill->id,
            'amount'      => 5000,
            'paid_at'     => '2025-05-10',
            'recorded_by' => $user->id,
        ]);

        // 支出
        Expense::factory()->create([
            'category_id'  => $cat->id,
            'amount'       => 2000,
            'expense_date' => '2025-05-15',
            'recorded_by'  => $user->id,
        ]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/balance?year=2025');

        $response->assertOk();
        $data = $response->json();

        $may = collect($data['months'])->firstWhere('month', 5);
        $this->assertEquals(5000, $may['income']);
        $this->assertEquals(2000, $may['expense']);
        $this->assertEquals(3000, $may['balance']);
        $this->assertEquals(5000, $data['total_income']);
        $this->assertEquals(2000, $data['total_expense']);
        $this->assertEquals(3000, $data['total_balance']);
    }

    public function test_overdue_report_returns_unpaid_and_overdue_bills(): void
    {
        Bill::factory()->create(['status' => 'paid']);
        Bill::factory()->create(['status' => 'unpaid']);
        Bill::factory()->overdue()->create();
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/overdue');

        $response->assertOk();
        $data = $response->json();
        $this->assertCount(2, $data['bills']);
        $this->assertEquals(2, $data['count']);
    }

    public function test_overdue_report_calculates_total(): void
    {
        Bill::factory()->create(['status' => 'unpaid', 'amount' => 2000]);
        Bill::factory()->overdue()->create(['amount' => 3000]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/overdue');

        $response->assertOk();
        $this->assertEquals(5000, $response->json('total'));
    }

    public function test_overdue_report_can_filter_by_year_and_month(): void
    {
        Bill::factory()->create(['status' => 'unpaid', 'year' => 2025, 'month' => 3]);
        Bill::factory()->create(['status' => 'unpaid', 'year' => 2025, 'month' => 6]);
        $this->actingAsAdmin();

        $response = $this->getJson('/api/reports/overdue?year=2025&month=3');

        $response->assertOk();
        $this->assertCount(1, $response->json('bills'));
    }
}
