<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Income;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Http\Traits\ExpenseTrait;

class ExpenseController extends Controller
{
    use ExpenseTrait;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         // Return the report as JSON
         return response()->json($this->prepareReportData(request()));
    }

     /**
     * Get advice from AI based on the report
     */
   
    public function getAdvice(Request $request)
    {
        $reportData = $this->prepareReportData($request);

        // Prepare the prompt to request structured JSON
        $prompt = "
        You are a smart financial assistant. Use the following financial data to optimize the user's spending plan.
        Data: " . json_encode($reportData) . "
        
        Task:
        1. Analyze the 'planned_amount' vs 'actual_spent' for each category.
        2. Propose a new 'expected_amount' for each category to better reflect actual spending habits and realistic saving goals.
        3. If a category is consistently over budget, increase the planned amount slightly. If under budget, decrease it to save more.
        
        Output Format:
        Return ONLY a raw JSON array (no markdown, no ```json tags). 
        The JSON array must contain objects with the following keys:
        - \"category_id\": (integer) The ID of the category.
        - \"new_expected_amount\": (numeric) The new recommended budget amount.
        - \"reason\": (string) A short explanation for the change (in Arabic).
        ";

        // 1. Call OpenRouter API
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openrouter.key'),
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'), // OpenRouter requires this
                'X-Title' => config('app.name'),
            ])->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'openrouter/auto', 
                'messages' => [
                    ['role' => 'user', 'content' => $prompt . " \n IMPORTANT: Be extremely brief. Return ONLY the JSON array."]
                ],
                'max_tokens' => 1000 
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $responseText = $data['choices'][0]['message']['content'] ?? '';

                // 2. Clean and Parse JSON
                $cleanJson = trim(str_replace(['```json', '```'], '', $responseText));
                $recommendations = json_decode($cleanJson, true);

                // 3. Update Database if JSON is valid
                if (json_last_error() === JSON_ERROR_NONE && is_array($recommendations)) {
                    foreach ($recommendations as $rec) {
                        if (isset($rec['category_id']) && isset($rec['new_expected_amount'])) {
                            Category::where('id', $rec['category_id'])->update([
                                'expected_amount' => $rec['new_expected_amount']
                            ]);
                        }
                    }
                    $message = "تم تحديث خطة الميزانية بنجاح بناءً على اقتراحات OpenRouter.";
                } else {
                    $recommendations = null;
                    $message = "فشل في قراءة اقتراحات الذكاء الاصطناعي بشكل صحيح. (Raw: $responseText)";
                }
            } else {
                throw new \Exception("OpenRouter API Error: " . $response->body());
            }
        } catch (\Exception $e) {
            $recommendations = null;
            $message = "حدث خطأ أثناء الاتصال بالذكاء الاصطناعي: " . $e->getMessage();
        }

        return response()->json([
            'message' => $message,
            'original_report' => $reportData,
            'ai_recommendations' => $recommendations
        ]);
    }
 
    
    /**
     * Store a newly created resource in storage.
     */
   
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric',
            'expense_date' => 'required|date',
        ]);
        $category = Category::find($request->category_id);
        
        $expense = Expense::query()->create([
            'user_id' => $request->user_id,
            'category_id' => $request->category_id,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'year' => $request->year ?? date('Y', strtotime($request->expense_date)),
            'month' => $request->month ?? date('n', strtotime($request->expense_date)), // 'n' gives 1-12 without leading zeros
        ]);

        $actualSpent = $category->expenses()->where('user_id', $request->user_id)
            ->where('month', $expense->month)
            ->where('year', $expense->year)
            ->sum('amount');
 
        $json = [
            'status' => true,
            'message' => "Successful Added Expenses",
            'code' => 200,
            'data' => $expense,
            'budget_status' => $actualSpent <= $category->expected_amount ? 'Under Budget' : 'Over Budget',
            'budget_details' => [
                'expected' => $category->expected_amount,
                'actual_spent' => $actualSpent,
                'remaining' => $category->expected_amount - $actualSpent
            ]
        ];
        
        return response()->json($json);
    }

   

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $expense = Expense::query()->where('user_id', $request->user_id)->where('id', $id)->first();
        if (!$expense) {
            return response()->json(['error' => 'Expense not found'], 404);
        }
        
       $status = $expense->update($request->all());
       $json = [
        'status' => $status ? true : false ,
         'message' => $status ? "Successful Updated Expenses": "Failed Updated" ,
        'code' => $status ? 200 : 400
       ];

        return response()->json($json);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $expense = Expense::find($id);
        if ($expense) {
            $expense->delete();
            return response()->json([
                'message' => 'Expense deleted successfully',
            ]);
        } else {
            return response()->json([
                'error' => 'Expense not found',
            ], 404);
        }
    }
}
