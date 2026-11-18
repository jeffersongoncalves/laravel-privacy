<?php

/*
 * Two minimal suites ("alpha" and "beta") built the way the real ones are: thin subclasses naming their
 * config, models, enums and events. Loaded from tests/Pest.php.
 */

namespace JeffersonGoncalves\Privacy\Tests\Fixtures;

use Attribute;
use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Privacy\Attributes\PersonalDataAttribute;
use JeffersonGoncalves\Privacy\AuditLog\AuditLog as BaseAuditLog;
use JeffersonGoncalves\Privacy\AuditLog\AuditLogger as BaseAuditLogger;
use JeffersonGoncalves\Privacy\AuditLog\Concerns\AuditsPersonalData;
use JeffersonGoncalves\Privacy\Concerns\AppliesAnonymizeStrategy;
use JeffersonGoncalves\Privacy\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Privacy\Consent\Concerns\HasConsents;
use JeffersonGoncalves\Privacy\Consent\ConsentManager as BaseConsentManager;
use JeffersonGoncalves\Privacy\Consent\ConsentRecord as BaseConsentRecord;
use JeffersonGoncalves\Privacy\Consent\ConsentRecorded as BaseConsentRecorded;
use JeffersonGoncalves\Privacy\Contracts\AnonymizeStrategy as AnonymizeStrategyContract;
use JeffersonGoncalves\Privacy\Contracts\HasPersonalData;
use JeffersonGoncalves\Privacy\Events\PersonalDataAnonymized;
use JeffersonGoncalves\Privacy\Privacy;
use JeffersonGoncalves\Privacy\Retention\RetentionManager as BaseRetentionManager;
use JeffersonGoncalves\Privacy\Retention\Tombstone as BaseTombstone;

enum AlphaStrategy: string implements AnonymizeStrategyContract
{
    use AppliesAnonymizeStrategy;

    const HASH_KEY = 'alpha.hash_key';

    case Nullify = 'nullify';
    case Hash = 'hash';
    case Mask = 'mask';
    case Keep = 'keep';
}

enum AlphaCategory: string
{
    case Personal = 'personal';
    case Sensitive = 'sensitive';
}

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class AlphaPersonalData extends PersonalDataAttribute
{
    public function __construct(
        public string $column,
        public AlphaCategory $category = AlphaCategory::Personal,
        public ?string $retention = null,
        AlphaStrategy $anonymize = AlphaStrategy::Nullify,
    ) {
        $this->anonymize = $anonymize;
    }

    public function toArray(): array
    {
        return ['column' => $this->column, 'category' => $this->category->value, 'retention' => $this->retention, 'anonymize' => $this->anonymize->value];
    }
}

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class BetaPersonalData extends PersonalDataAttribute
{
    public function __construct(
        public string $column,
        public ?string $retention = null,
        AlphaStrategy $anonymize = AlphaStrategy::Nullify,
    ) {
        $this->anonymize = $anonymize;
    }

    public function toArray(): array
    {
        return ['column' => $this->column, 'retention' => $this->retention, 'anonymize' => $this->anonymize->value];
    }
}

class AlphaAnonymized extends PersonalDataAnonymized {}

class BetaAnonymized extends PersonalDataAnonymized {}

trait AlphaInteracts
{
    use InteractsWithPersonalData;

    protected static function personalDataAttribute(): string
    {
        return AlphaPersonalData::class;
    }

    protected static function personalDataAnonymizedEvent(): string
    {
        return AlphaAnonymized::class;
    }
}

class Alpha extends Privacy
{
    protected string $config = 'alpha';
}

class Beta extends Privacy
{
    protected string $config = 'beta';
}

/**
 * @property string|null $name
 * @property string|null $email
 * @property string|null $document
 */
#[AlphaPersonalData('name', retention: '1 year', anonymize: AlphaStrategy::Mask)]
#[AlphaPersonalData('email', retention: '1 year', anonymize: AlphaStrategy::Hash)]
#[AlphaPersonalData('document', anonymize: AlphaStrategy::Keep)]
#[BetaPersonalData('name')]
class Customer extends Model implements HasPersonalData
{
    use AlphaInteracts;
    use AuditsPersonalData;
    use HasConsents;

    protected $guarded = [];

    protected static function auditLogger(): BaseAuditLogger
    {
        return app(AlphaAuditLogger::class);
    }

    protected static function consentManager(): BaseConsentManager
    {
        return app(AlphaConsents::class);
    }

    protected static function consentRecordModel(): string
    {
        return AlphaConsentRecord::class;
    }
}

class AlphaTombstone extends BaseTombstone
{
    public function getTable(): string
    {
        return 'alpha_tombstones';
    }
}

class AlphaRetention extends BaseRetentionManager
{
    protected string $config = 'alpha-retention';

    protected string $tombstone = AlphaTombstone::class;

    public function __construct(Alpha $alpha)
    {
        parent::__construct($alpha);
    }
}

#[AlphaPersonalData('ip_address')]
#[AlphaPersonalData('user_agent')]
class AlphaAuditLog extends BaseAuditLog implements HasPersonalData
{
    use AlphaInteracts;

    public function getTable(): string
    {
        return 'alpha_audit_logs';
    }
}

class AlphaAuditLogger extends BaseAuditLogger
{
    protected string $config = 'alpha-audit-log';

    protected string $model = AlphaAuditLog::class;

    public function __construct(Alpha $alpha)
    {
        parent::__construct($alpha);
    }
}

enum AlphaConsentAction: string
{
    case Granted = 'granted';
    case Revoked = 'revoked';
}

#[AlphaPersonalData('ip_address')]
#[AlphaPersonalData('user_agent')]
class AlphaConsentRecord extends BaseConsentRecord
{
    use AlphaInteracts;

    protected string $actionEnum = AlphaConsentAction::class;

    public function getTable(): string
    {
        return 'alpha_consents';
    }
}

class AlphaConsentRecorded extends BaseConsentRecorded {}

class AlphaConsents extends BaseConsentManager
{
    protected string $config = 'alpha-consent';

    protected string $model = AlphaConsentRecord::class;

    protected string $event = AlphaConsentRecorded::class;
}
