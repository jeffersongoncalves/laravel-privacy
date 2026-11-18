<?php

namespace JeffersonGoncalves\Privacy\Consent\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use JeffersonGoncalves\Privacy\Consent\ConsentManager;
use JeffersonGoncalves\Privacy\Consent\ConsentRecord;

/**
 * For the models giving consent (users). A suite's trait uses this one and names its manager and record model.
 *
 * @mixin Model
 */
trait HasConsents
{
    abstract protected static function consentManager(): ConsentManager;

    /**
     * @return class-string<ConsentRecord>
     */
    abstract protected static function consentRecordModel(): string;

    /**
     * @return MorphMany<ConsentRecord, $this>
     */
    public function consents(): MorphMany
    {
        return $this->morphMany(static::consentRecordModel(), 'subject');
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function grantConsent(string $purpose, ?string $version = null, ?string $source = null, array $metadata = []): ConsentRecord
    {
        return static::consentManager()->grant($this, $purpose, $version, $source, $metadata);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function revokeConsent(string $purpose, ?string $source = null, array $metadata = []): ConsentRecord
    {
        return static::consentManager()->revoke($this, $purpose, $source, $metadata);
    }

    public function hasConsent(string $purpose, ?string $version = null): bool
    {
        return static::consentManager()->has($this, $purpose, $version);
    }
}
