<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Car;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Car $car;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeaders([
            'referer' => 'http://localhost:3000',
            'origin' => 'http://localhost:3000',
            'accept' => 'application/json',
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@hassanmotors.pk',
        ]);

        $brand = Brand::factory()->create(['name' => 'Toyota', 'slug' => 'toyota']);
        $this->car = Car::factory()->create([
            'brand_id' => $brand->id,
            'model' => 'Corolla',
            'variant' => 'Altis Grande',
            'year' => 2022,
            'price' => 6800000.00,
            'slug' => 'toyota-corolla-altis-grande-2022',
            'publication_status' => 'published',
        ]);

        Setting::updateOrCreate(
            ['key' => 'showroom_whatsapp'],
            ['value' => '+923041142642', 'group' => 'contact', 'is_public' => true]
        );
    }

    /**
     * Public user can submit a general inquiry and receives a WhatsApp deep link.
     */
    public function test_public_user_can_submit_general_inquiry_and_receives_whatsapp_link(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'customer_name' => 'Usman Tariq',
            'phone' => '03001234567',
            'message' => 'Do you offer vehicle financing or trade-ins?',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['lead_id', 'whatsapp_url'],
            ]);

        $leadId = $response->json('data.lead_id');
        $whatsappUrl = $response->json('data.whatsapp_url');

        // Verify database persistence before WhatsApp navigation
        $this->assertDatabaseHas('leads', [
            'id' => $leadId,
            'customer_name' => 'Usman Tariq',
            'phone' => '+923001234567',
            'message' => 'Do you offer vehicle financing or trade-ins?',
            'status' => 'new',
            'source' => 'contact_page',
            'car_id' => null,
            'whatsapp_clicked_at' => null,
        ]);

        // WhatsApp link contains target number and customer information
        $this->assertStringContainsString('https://wa.me/923041142642', $whatsappUrl);
        $this->assertStringContainsString('Usman%20Tariq', $whatsappUrl);
    }

    /**
     * Public user can submit a car-specific inquiry.
     */
    public function test_public_user_can_submit_car_specific_inquiry(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'car_id' => $this->car->id,
            'customer_name' => 'Ahmad Raza',
            'phone' => '+923041142642',
            'message' => 'Is this Corolla available for immediate delivery?',
        ]);

        $response->assertStatus(201);
        $leadId = $response->json('data.lead_id');
        $whatsappUrl = $response->json('data.whatsapp_url');

        $this->assertDatabaseHas('leads', [
            'id' => $leadId,
            'car_id' => $this->car->id,
            'source' => 'car_detail',
        ]);

        // Prefilled message must contain the car information
        $this->assertStringContainsString('Corolla', urldecode($whatsappUrl));
        $this->assertStringContainsString('6,800,000', urldecode($whatsappUrl));
    }

    /**
     * Honeypot field traps and rejects automated bot spam.
     */
    public function test_honeypot_spam_trap_rejects_bot_submissions(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'customer_name' => 'Spam Bot',
            'phone' => '+923001234567',
            'website' => 'http://spam-site.xyz',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website']);

        $this->assertDatabaseCount('leads', 0);
    }

    /**
     * Lead submission requires valid name and phone.
     */
    public function test_submission_validation_requires_valid_name_and_phone(): void
    {
        $response = $this->postJson('/api/v1/leads', [
            'customer_name' => 'A', // too short
            'phone' => 'abc',       // invalid phone
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_name', 'phone']);
    }

    /**
     * Public submission rate limiter triggers after 5 attempts.
     */
    public function test_public_lead_submission_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/leads', [
                'customer_name' => "User {$i}",
                'phone' => '+92300123456' . $i,
            ])->assertStatus(201);
        }

        // 6th attempt should be blocked by rate limiter
        $this->postJson('/api/v1/leads', [
            'customer_name' => 'User 6',
            'phone' => '+923001234566',
        ])->assertStatus(429);
    }

    /**
     * Recording WhatsApp click updates timestamp without claiming message was sent.
     */
    public function test_customer_can_track_whatsapp_click(): void
    {
        $lead = Lead::factory()->create([
            'whatsapp_clicked_at' => null,
        ]);

        $response = $this->postJson("/api/v1/leads/{$lead->id}/whatsapp-clicked");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $lead->refresh();
        $this->assertNotNull($lead->whatsapp_clicked_at);

        // A second click should preserve original timestamp
        $originalClickTime = $lead->whatsapp_clicked_at;
        $this->postJson("/api/v1/leads/{$lead->id}/whatsapp-clicked");
        $lead->refresh();
        $this->assertEquals($originalClickTime->timestamp, $lead->whatsapp_clicked_at->timestamp);
    }

    /**
     * Unauthenticated requests to admin lead endpoints return 401.
     */
    public function test_unauthenticated_requests_to_admin_leads_return_401(): void
    {
        $this->getJson('/api/v1/admin/leads')->assertStatus(401);
        $this->getJson('/api/v1/admin/leads/1')->assertStatus(401);
        $this->patchJson('/api/v1/admin/leads/1/status', ['status' => 'contacted'])->assertStatus(401);
        $this->patchJson('/api/v1/admin/leads/1/notes', ['notes' => 'Internal note'])->assertStatus(401);
    }

    /**
     * Admin can view paginated leads and filter by status.
     */
    public function test_admin_can_view_and_filter_leads_by_status(): void
    {
        Lead::factory()->create(['status' => 'new', 'customer_name' => 'Customer New']);
        Lead::factory()->create(['status' => 'contacted', 'customer_name' => 'Customer Contacted']);

        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/api/v1/admin/leads?status=contacted');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'contacted')
            ->assertJsonPath('data.0.customer_name', 'Customer Contacted');
    }

    /**
     * Admin can search leads by customer name or phone.
     */
    public function test_admin_can_search_leads(): void
    {
        Lead::factory()->create(['customer_name' => 'Shahid Afridi', 'phone' => '+923009999999']);
        Lead::factory()->create(['customer_name' => 'Babar Azam', 'phone' => '+923008888888']);

        $response = $this->actingAs($this->admin, 'web')
            ->getJson('/api/v1/admin/leads?search=Shahid');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer_name', 'Shahid Afridi');
    }

    /**
     * Admin can view single lead details including car specifications.
     */
    public function test_admin_can_view_single_lead(): void
    {
        $lead = Lead::factory()->create([
            'car_id' => $this->car->id,
            'customer_name' => 'Kamran Akmal',
            'phone' => '+923007777777',
            'notes' => 'Customer asked for final cash price discount.',
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->getJson("/api/v1/admin/leads/{$lead->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $lead->id)
            ->assertJsonPath('data.customer_name', 'Kamran Akmal')
            ->assertJsonPath('data.notes', 'Customer asked for final cash price discount.')
            ->assertJsonPath('data.car.model', 'Corolla')
            ->assertJsonPath('data.car.brand.name', 'Toyota');
    }

    /**
     * Admin can update lead status and contacted_at is automatically recorded.
     */
    public function test_admin_can_update_lead_status_and_contacted_at_is_recorded(): void
    {
        $lead = Lead::factory()->create([
            'status' => 'new',
            'contacted_at' => null,
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->patchJson("/api/v1/admin/leads/{$lead->id}/status", [
                'status' => 'contacted',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'contacted');
        $this->assertNotNull($response->json('data.contacted_at'));

        $lead->refresh();
        $this->assertSame('contacted', $lead->status);
        $this->assertNotNull($lead->contacted_at);
    }

    /**
     * Admin can update internal notes.
     */
    public function test_admin_can_update_internal_notes(): void
    {
        $lead = Lead::factory()->create(['notes' => null]);

        $response = $this->actingAs($this->admin, 'web')
            ->patchJson("/api/v1/admin/leads/{$lead->id}/notes", [
                'notes' => 'Follow up on Friday after Jummah prayers.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.notes', 'Follow up on Friday after Jummah prayers.');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'notes' => 'Follow up on Friday after Jummah prayers.',
        ]);
    }

    /**
     * Lead preserves car relationship even when car is soft deleted.
     */
    public function test_lead_preserves_car_details_when_car_is_soft_deleted(): void
    {
        $lead = Lead::factory()->create([
            'car_id' => $this->car->id,
            'customer_name' => 'Fawad Alam',
        ]);

        // Soft delete the car
        $this->car->delete();
        $this->assertSoftDeleted('cars', ['id' => $this->car->id]);

        $response = $this->actingAs($this->admin, 'web')
            ->getJson("/api/v1/admin/leads/{$lead->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.car.id', $this->car->id)
            ->assertJsonPath('data.car.model', 'Corolla');
    }

    /**
     * Public users cannot list or view leads directly.
     */
    public function test_public_users_cannot_access_lead_listing_or_details(): void
    {
        $this->getJson('/api/v1/leads')->assertStatus(405); // Method not allowed
        $this->getJson('/api/v1/leads/1')->assertStatus(404); // Not found
    }
}
