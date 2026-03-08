<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    
    protected $fillable = [
        'amount',
        'category_id',
        'user_id',
        'expense_date',
        'year',
        'month'
    ];
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // scope for filtering expenses by month and year
    public function scopeFilterDate($query, $month, $year)
    {
        if ($month) {
            $query->where('month', $month);
        }
        if ($year) {
            $query->where('year', $year);
        }
        return $query;
    }
        

}
