<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
    /**
     * A basic test example.
     */
  public function test_add_expense(): void
{
     $this->withoutMiddleware(); 

    $response = $this->post('/api/expenses', [ 
        'name' => 'Test Expense',
        'amount' => 100,
        'categories_id' => 1,
        'date' => '2022-01-01',
    ]);

     $response->assertStatus(201);   
}

}
