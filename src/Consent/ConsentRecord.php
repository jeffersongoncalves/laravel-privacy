<?php

namespace JeffersonGoncalves\Privacy\Consent;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use JeffersonGoncalves\Privacy\Attributes\PersonalDataAttribute;
use JeffersonGoncalves\Privacy\Contracts\HasPersonalData;
use LogicException;

/**
 * Base of every suite's ConsentRecord: one immutable consent event. The ledger is append-only: a new decision
 * is a new row. Only the personal data columns may change, so the person can still be anonymized. A suite
 * overrides getTable(), sets its ConsentAction enum (`protected string $actionEnum = ConsentAction::class;`)
 * and declares ip_address and user_agent with its #[PersonalData] attribute.
 *
 * @property BackedEnum $action granted or revoked
 * @property string $purpose
 * @property string|null $version
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $expires_at
 */
abstract class ConsentRecord extends Model implements HasPersonalData
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    /** @var class-string<BackedEnum> */
    protected string $actionEnum;

    protected function casts(): array
    {
        return [
            'action' => $this->actionEnum,
            'expires_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (ConsentRecord $record): void {
            $allowed = array_map(fn (PersonalDataAttribute $field): string => $field->column, static::personalDataFields());

            if (array_diff(array_keys($record->getDirty()), $allowed) !== []) {
                throw new LogicException('Consent records are append-only: record a new grant or revoke instead.');
            }
        });

        static::deleting(function (): void {
            throw new LogicException('Consent records are append-only and cannot be deleted. Anonymize them instead.');
        });
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->action->value === 'granted'
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
