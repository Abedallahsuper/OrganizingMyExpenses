<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // get user id from authenticated user by guard api 
        $user_id = Auth::guard('api')->id();
        // get all categories of the authenticated user
         $categories = Category::query()->with('user')
          ->where('user_id',$user_id)->get();// or Auth::user()->categories
     
        $status = $categories->isNotEmpty();
      
        $json = [
            'categories' => $categories,
            'code' => $status ? 200 : 400,
            'message' => $status ? 'Categories retrieved successfully' : 'Categories not found',
        ];

        return response()->json($json);
    }

    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'user_id' => 'required|exists:users,id',
            'expected_amount' => 'required|numeric',
            'year' => 'sometimes|integer',
            'month' => 'sometimes|integer'
        ]);

        $category = Category::create($validatedData);

        return response()->json([
            'code' => 201,
            'message' => 'Category created successfully',
            'status' => true,
            'category' => $category
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::with('user')->findOrFail($id);
     
        return response()->json([
            'category' => $category,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);
        $validated = $request->validate([ 
            'user_id' => 'sometimes|numeric',
            'name' => 'sometimes|string' ,
            'expected_amount' => 'sometimes|numeric',
            'year' => 'sometimes|numeric',
            'month' => 'sometimes|numeric'
        ]);

        if (empty($validated)) {
            return response()->json([
                'message' => 'No data provided to update',
            ], 400);
        }

        $status = $category->update($validated);

        return response()->json([
            'message' => $status ? 'Category updated successfully' : 'Category not updated',
            'status' => $status,
            'code' => $status ? 200 : 400,
            "category" => $category
        ]);  
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::find($id);
        
        if (!$category) {
            return response()->json([
                'message' => 'Category not found',
                'status' => false,
                'code' => 404
            ], 404);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
            'status' => true,
            'code' => 200
        ]);
    }
}
