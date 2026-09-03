<?php

namespace Tests\Feature\System;

use App\Models\System\Setting;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * Configuration Management (Phase 1) — settings foundation (unit-level).
 *
 * These tests exercise the Setting model's configuration logic in isolation,
 * without a database table, so they run on any connection (including the
 * project's default sqlite :memory:).
 *
 * End-to-end CRUD/secret-flows are verified against a MySQL-backed database;
 * they cannot run under the sqlite/migration setup because the pre-existing
 * `settings` table is created by a raw MySQL migration.
 */
class ConfigurationManagementTest extends TestCase
{
    public function test_encrypted_secret_round_trips_but_is_not_plaintext(): void
    {
        $setting = new Setting([
            'value' => Crypt::encryptString('SuperSecret123'),
            'type' => 'password',
            'is_encrypted' => true,
            'group' => 'email',
        ]);

        $this->assertNotEquals('SuperSecret123', $setting->value);
        $this->assertTrue($setting->is_encrypted);
        $this->assertEquals('SuperSecret123', $setting->decryptValue());
    }

    public function test_encrypted_secret_with_invalid_ciphertext_decrypts_to_null(): void
    {
        $setting = new Setting([
            'value' => 'not-a-valid-ciphertext',
            'type' => 'password',
            'is_encrypted' => true,
            'group' => 'email',
        ]);

        $this->assertNull($setting->decryptValue());
    }

    public function test_secret_type_is_detected(): void
    {
        $this->assertTrue(Setting::isSecretType('password'));
        $this->assertFalse(Setting::isSecretType('string'));
        $this->assertFalse(Setting::isSecretType('email'));
    }

    public function test_supported_types_are_exposed(): void
    {
        $this->assertContains('string', Setting::SUPPORTED_TYPES);
        $this->assertContains('password', Setting::SUPPORTED_TYPES);
        $this->assertContains('timezone', Setting::SUPPORTED_TYPES);
        $this->assertContains('integer', Setting::SUPPORTED_TYPES);
        $this->assertContains('boolean', Setting::SUPPORTED_TYPES);
        $this->assertNotContains('bogus', Setting::SUPPORTED_TYPES);
    }

    public function test_integer_typed_value_is_cast(): void
    {
        $setting = new Setting(['value' => '30', 'type' => 'integer']);
        $this->assertSame(30, $setting->typedValue());
        $this->assertIsInt($setting->typedValue());
    }

    public function test_boolean_typed_value_is_cast(): void
    {
        $setting = new Setting(['value' => '1', 'type' => 'boolean']);
        $this->assertTrue($setting->typedValue());
    }

    public function test_is_public_and_sort_order_cast_to_scalars(): void
    {
        $setting = new Setting([
            'value' => 'x',
            'type' => 'string',
            'is_public' => true,
            'is_encrypted' => false,
            'sort_order' => '40',
        ]);

        $this->assertTrue($setting->is_public);
        $this->assertFalse($setting->is_encrypted);
        $this->assertSame(40, $setting->sort_order);
    }
}
