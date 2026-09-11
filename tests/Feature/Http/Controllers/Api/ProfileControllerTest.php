<?php

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

it('updates the authenticated user profile', function () {
    $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.test', 'locale' => 'ru']);

    $response = $this->actingAs($user)->patchJson('/api/profile', [
        'name' => 'New Name',
        'email' => 'new@example.test',
        'locale' => 'en',
    ]);

    $response->assertOk();
    $response->assertJsonPath('user.name', 'New Name');
    $response->assertJsonPath('user.email', 'new@example.test');
    $response->assertJsonPath('user.locale', 'en');

    expect($user->fresh())
        ->name->toBe('New Name')
        ->email->toBe('new@example.test')
        ->locale->toBe('en');
});

it('rejects a profile update with an email already taken by another user', function () {
    User::factory()->create(['email' => 'taken@example.test']);
    $user = User::factory()->create(['email' => 'mine@example.test']);

    $response = $this->actingAs($user)->patchJson('/api/profile', [
        'name' => $user->name,
        'email' => 'taken@example.test',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['email']);
});

it('rejects a profile update with an unsupported locale', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->patchJson('/api/profile', [
        'name' => $user->name,
        'email' => $user->email,
        'locale' => 'fr',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['locale']);
});

it('allows a user to keep their own email when updating the profile', function () {
    $user = User::factory()->create(['email' => 'me@example.test']);

    $response = $this->actingAs($user)->patchJson('/api/profile', [
        'name' => 'Same Email',
        'email' => 'me@example.test',
    ]);

    $response->assertOk();
});

it('updates the password when the current password is correct', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $response = $this->actingAs($user)->putJson('/api/profile/password', [
        'current_password' => 'old-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertOk();
    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

it('rejects a password update when the current password is wrong', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password')]);

    $response = $this->actingAs($user)->putJson('/api/profile/password', [
        'current_password' => 'wrong-password',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['current_password']);
    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('rejects unauthenticated profile requests', function () {
    $this->patchJson('/api/profile', ['name' => 'X', 'email' => 'x@example.test'])->assertUnauthorized();
    $this->putJson('/api/profile/password', ['current_password' => 'x', 'password' => 'y', 'password_confirmation' => 'y'])->assertUnauthorized();
});
