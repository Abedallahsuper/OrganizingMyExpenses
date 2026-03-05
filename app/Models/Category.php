<?php

namespace App\Models;
    
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{

    protected $fillable = [
        'name',
        'user_id' ,
        'expected_amount',
        'year',
        'month'
    ];
        protected $casts = [
        'expected_amount' => 'decimal:2',
    ];
   
    
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
