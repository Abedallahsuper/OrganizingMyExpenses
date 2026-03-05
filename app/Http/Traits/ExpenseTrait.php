<?php 
namespace App\Http\Traits;

use App\Models\Expense;
use App\Models\Category;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
trait ExpenseTrait
{
    public function prepareReportData(Request $request)
    {
        // Use user_id from request if available, otherwise fallback to Authenticated user
        $userId = $request->user_id ?? Auth::guard('api')->id();
        $month = $request->month;
        $year = $request->year;

        // 1. Calculate Monthly Income
        $monthlyIncome = Income::query()
            ->where('user_id', $userId)
            ->where('month', $month)
            ->where('year', $year)
            ->sum('amount');

        // 2. Calculate Spending Plan vs Actual Expenses per Category
        $categories = Category::query()
            ->where('user_id', $userId)
            ->filterDate($month, $year)
            ->with(['expenses' => function ($query) use ($month, $year) {
                $query->where('month', $month)->where('year', $year);
            }])
            ->get();

        $spendingPlan = $categories->map(function ($category) {
            $actualSpent = $category->expenses->sum('amount');

            return [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'planned_amount' => $category->expected_amount,
                'actual_spent' => $actualSpent,
                'difference' => $category->expected_amount - $actualSpent,
            ];
        });

        $totalPlanned = $spendingPlan->sum('planned_amount');
        $totalActual = $spendingPlan->sum('actual_spent');

        // Return Data as Array (Easier for AI and Controller processing)
        return [
            'report_summary' => [
                'user_id' => $userId,
                'month' => $month,
                'year' => $year,
                'total_income' => (float)$monthlyIncome,
                'total_planned_spending' => (float)$totalPlanned,
                'total_actual_spending' => (float)$totalActual,
                'remaining_from_income' => (float)$monthlyIncome - (float)$totalActual,
                'plan_vs_actual_status' => ($totalPlanned >= $totalActual) ? 'Under Budget' : 'Over Budget'
            ],
            'categories_details' => $spendingPlan,
            'original_expenses' => Expense::filterDate($month, $year)
                ->where('user_id', $userId)
                ->get()
        ];
    }
}