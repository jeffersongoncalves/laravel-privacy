<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use JeffersonGoncalves\Privacy\Retention\Listeners\RecordTombstones;
use JeffersonGoncalves\Privacy\Retention\RetentionManager;
use JeffersonGoncalves\Privacy\Tests\Fixtures\Alpha;
use JeffersonGoncalves\Privacy\Tests\Fixtures\AlphaAnonymized;
use JeffersonGoncalves\Privacy\Tests\Fixtures\AlphaAuditLog;
use JeffersonGoncalves\Privacy\Tests\Fixtures\AlphaAuditLogger;
use JeffersonGoncalves\Privacy\Tests\Fixtures\AlphaConsentAction;
use JeffersonGoncalves\Privacy\Tests\Fixtures\AlphaConsentRecorded;
use JeffersonGoncalves\Privacy\Tests\Fixtures\AlphaConsents;
use JeffersonGoncalves\Privacy\Tests\Fixtures\AlphaPersonalData;
use JeffersonGoncalves\Privacy\Tests\Fixtures\AlphaRetention;
use JeffersonGoncalves\Privacy\Tests\Fixtures\AlphaTombstone;
use JeffersonGoncalves\Privacy\Tests\Fixtures\BetaAnonymized;
use JeffersonGoncalves\Privacy\Tests\Fixtures\Customer;

beforeEach(function () {
    config(['alpha.models' => [Customer::class]]);
});

it('reads only the suite\'s own declarations', function () {
    expect(array_map(fn ($f) => $f::class, Customer::personalDataFields()))->each->toBe(AlphaPersonalData::class)
        ->and(app(Alpha::class)->inventory())->toHaveCount(3)
        ->and(app(Alpha::class)->inventory()[0])->toMatchArray(['model' => Customer::class, 'table' => 'customers', 'column' => 'name']);
});

it('anonymizes with each strategy and fires only the suite\'s event', function () {
    Event::fake([AlphaAnonymized::class, BetaAnonymized::class]);
    $customer = Customer::create(['name' => 'Maria', 'email' => 'maria@example.com', 'document' => '123']);

    $customer->anonymizePersonalData();

    expect($customer->refresh()->name)->toBe('M****')
        ->and($customer->email)->toBe(hash_hmac('sha256', 'maria@example.com', config('app.key')))
        ->and($customer->document)->toBe('123');
    Event::assertDispatched(AlphaAnonymized::class, fn ($event) => $event->columns === ['name', 'email']);
    Event::assertNotDispatched(BetaAnonymized::class);
});

it('rejects models that do not implement the contract', function () {
    config(['alpha.models' => [stdClass::class]]);

    app(Alpha::class)->models();
})->throws(InvalidArgumentException::class, 'alpha.models entries must be Eloquent models');

it('anonymizes expired columns once, tombstones them and reapplies after a restore', function () {
    $old = Customer::create(['name' => 'Ana', 'email' => 'ana@example.com']);
    $old->forceFill(['created_at' => now()->subYears(2)])->save();
    Customer::create(['name' => 'Bia', 'email' => 'bia@example.com']);

    $report = app(AlphaRetention::class)->apply();

    expect(collect($report)->pluck('records')->all())->toBe([1, 1])
        ->and($old->refresh()->name)->toBe('A**')
        ->and(AlphaTombstone::count())->toBe(2)
        ->and(app(AlphaRetention::class)->apply()[0]['records'])->toBe(0);

    $old->forceFill(['name' => 'Ana'])->saveQuietly();

    expect(app(AlphaRetention::class)->reapply())->toBe(1)
        ->and($old->refresh()->name)->toBe('A**');
});

it('tombstones the suite\'s anonymizations through the listener', function () {
    Event::listen(AlphaAnonymized::class, RecordTombstones::class);
    app()->bind(RetentionManager::class, AlphaRetention::class);

    Customer::create(['name' => 'Caio', 'email' => 'caio@example.com'])->anonymizePersonalData();

    expect(AlphaTombstone::pluck('column')->all())->toBe(['name', 'email']);
});

it('chains the audit log and detects tampering', function () {
    $customer = Customer::create(['name' => 'Davi']);
    $customer->update(['name' => 'David', 'updated_at' => now()]);
    $customer->auditAccess('support');
    $logger = app(AlphaAuditLogger::class);

    expect(AlphaAuditLog::pluck('action')->all())->toBe(['updated', 'viewed'])
        ->and($logger->verify())->toBeNull();

    DB::table('alpha_audit_logs')->where('action', 'viewed')->update(['purpose' => 'marketing']);

    expect($logger->verify())->toBe(2)
        ->and(fn () => AlphaAuditLog::first()->update(['action' => 'x']))->toThrow(LogicException::class);
});

it('keeps an append-only consent ledger with the suite\'s action enum and event', function () {
    Event::fake([AlphaConsentRecorded::class]);
    config(['alpha-consent.purposes' => ['newsletter' => ['version' => 'v2']]]);
    $consents = app(AlphaConsents::class);
    $customer = Customer::create(['name' => 'Eva']);

    $consents->grant('visitor-1', 'newsletter');
    $consents->claim('visitor-1', $customer);

    expect($consents->has($customer, 'newsletter'))->toBeTrue()
        ->and($consents->latest($customer, 'newsletter')->action)->toBe(AlphaConsentAction::Granted)
        ->and($consents->latest($customer, 'newsletter')->getAttribute('source'))->toBe('claimed')
        ->and($consents->has($customer, 'newsletter', 'v3'))->toBeFalse();

    $consents->revoke($customer, 'newsletter');

    expect($consents->has($customer, 'newsletter'))->toBeFalse()
        ->and($consents->history($customer))->toHaveCount(2)
        ->and(fn () => $consents->grant($customer, 'unknown'))->toThrow(InvalidArgumentException::class, 'alpha-consent.purposes');
    Event::assertDispatchedTimes(AlphaConsentRecorded::class, 3);
});

it('gives the subject model its consents', function () {
    $customer = Customer::create(['name' => 'Fabi']);

    $customer->grantConsent('terms');

    expect($customer->hasConsent('terms'))->toBeTrue()
        ->and($customer->consents()->count())->toBe(1);

    $customer->revokeConsent('terms');

    expect($customer->hasConsent('terms'))->toBeFalse();
});

it('measures k-anonymity', function () {
    foreach (['a', 'a', 'b'] as $name) {
        Customer::create(['name' => $name]);
    }

    expect(app(Alpha::class)->kAnonymity(Customer::class, ['name'], 2))
        ->toMatchArray(['rows' => 3, 'groups' => 2, 'k' => 1, 'rows_below_k' => 1]);
});
