<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Income;

use Illuminate\Support\Facades\Auth;
class IncomeController extends Controller
{
    /**
     * Display incomes of the authenticated user
     */
    public function index()
    {
        $userId = Auth::id();
        $incomes = Income::with('user')->where('user_id', $userId)->get();
        
        return response()->json([
            'incomes' => $incomes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
  
    public function store(Request $request)
    { 
        $validatedData = $request->validate([
            'amount' => 'required|numeric',
            'source' => 'required|string',
            'month' => 'sometimes|integer',
            'year' => 'sometimes|integer',
        ]);

        $validatedData['user_id'] = Auth::id();
        $income = Income::create($validatedData);
        
        return response()->json([
            'message' => 'Income created successfully',
            'status' => true,
            'income' => $income
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $income = Income::query()->with('user')->find($id);
        return response()->json([
            'income' => $income,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $income = Income::where('user_id', Auth::id())->findOrFail($id); 

        $validated = $request->validate([
            'amount' => 'sometimes|numeric',
            'source' => 'sometimes|string',
            'month'  => 'sometimes|integer',
            'year'   => 'sometimes|integer',
        ]);

        if (empty($validated)) {
            return response()->json([
                'message' => 'No data provided to update'
            ], 400);
        }

        $income->update($validated);

        return response()->json([
            'message' => 'Income updated successfully',
            'status' => true,
            'income' => $income
        ]);  
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $income = Income::query()->find($id);
        $status = $income->delete();
   
       $json = [
        'message' => $status ? 'Income deleted successfully' : 'Income not deleted',
        'status' => $status,
        'code' => $status ? 200 : 400,
        "income" => $income
    ];

       return response()->json($json);  
    }


}