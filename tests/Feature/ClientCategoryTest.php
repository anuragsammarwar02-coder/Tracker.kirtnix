<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_create_client_with_category(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->post(route('clients.store'), [
            'kx_code' => 'KX-TEST-CAT',
            'company_name' => 'Forex Pro Traders',
            'client_name' => 'John Doe',
            'category' => 'Forex & Currency Trading',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $client = Client::where('kx_code', 'KX-TEST-CAT')->first();
        $this->assertNotNull($client);
        $this->assertEquals('Forex & Currency Trading', $client->category);
    }

    public function test_can_update_client_category(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $client = Client::create([
            'kx_code' => 'KX-UPDATE-CAT',
            'company_name' => 'Crypto Bulls',
            'client_name' => 'Alice',
            'category' => 'Stock Market & Options Trading',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('clients.update', $client), [
            'kx_code' => $client->kx_code,
            'company_name' => 'Crypto Bulls',
            'client_name' => 'Alice',
            'category' => 'Crypto & Web3 Trading',
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertEquals('Crypto & Web3 Trading', $client->fresh()->category);
    }
}
