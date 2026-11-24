<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_settings_returns_only_allowlisted_public_keys(): void
    {
        Cache::flush();

        // Allowlisted public settings
        Setting::create([
            'key' => 'showroom_name',
            'value' => 'Hassan Motors',
            'group' => 'general',
            'is_public' => true,
        ]);
        Setting::create([
            'key' => 'showroom_phone',
            'value' => '+923041142642',
            'group' => 'contact',
            'is_public' => true,
        ]);

        // Non-allowlisted setting that happens to have is_public=true
        Setting::create([
            'key' => 'internal_crm_api_key',
            'value' => 'secret-token-12345',
            'group' => 'integrations',
            'is_public' => true,
        ]);

        // Non-allowlisted private setting
        Setting::create([
            'key' => 'smtp_password',
            'value' => 'supersecret',
            'group' => 'mail',
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/v1/settings');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.showroom_name', 'Hassan Motors')
            ->assertJsonPath('data.showroom_phone', '+923041142642');

        $data = $response->json('data');
        $this->assertArrayNotHasKey('internal_crm_api_key', $data, 'Non-allowlisted keys must never be exposed');
        $this->assertArrayNotHasKey('smtp_password', $data, 'Private keys must never be exposed');
    }

    public function test_allowlisted_setting_with_is_public_false_is_not_exposed(): void
    {
        Cache::flush();

        Setting::create([
            'key' => 'showroom_whatsapp',
            'value' => '+923041142642',
            'group' => 'contact',
            'is_public' => false, // explicitly hidden
        ]);

        $response = $this->getJson('/api/v1/settings');
        $response->assertOk();

        $data = $response->json('data');
        $this->assertArrayNotHasKey('showroom_whatsapp', $data);
    }

    public function test_targeted_cache_invalidation_when_allowlisted_setting_updated(): void
    {
        Cache::flush();

        $setting = Setting::create([
            'key' => 'showroom_phone',
            'value' => '+923041142642',
            'group' => 'contact',
            'is_public' => true,
        ]);

        // Prime cache
        $this->getJson('/api/v1/settings')->assertOk();
        $this->assertTrue(Cache::has('public_showroom_settings'));

        // Update allowlisted setting
        $setting->update(['value' => '+923000000000']);

        // Cache must be invalidated
        $this->assertFalse(Cache::has('public_showroom_settings'));
    }

    public function test_unrelated_internal_setting_does_not_invalidate_public_cache(): void
    {
        Cache::flush();

        Setting::create([
            'key' => 'showroom_name',
            'value' => 'Hassan Motors',
            'group' => 'general',
            'is_public' => true,
        ]);

        $internalSetting = Setting::create([
            'key' => 'internal_admin_theme',
            'value' => 'dark',
            'group' => 'admin',
            'is_public' => false,
        ]);

        // Prime cache
        $this->getJson('/api/v1/settings')->assertOk();
        $this->assertTrue(Cache::has('public_showroom_settings'));

        // Update internal setting
        $internalSetting->update(['value' => 'light']);

        // Cache should remain intact because key is not in ALLOWED_PUBLIC_KEYS
        $this->assertTrue(Cache::has('public_showroom_settings'), 'Unrelated internal setting update must not flush public cache');
    }
}
