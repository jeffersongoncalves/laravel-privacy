<?php

namespace JeffersonGoncalves\Privacy\Retention;

use Illuminate\Database\Eloquent\Model;

/**
 * Base of every suite's Tombstone model: an anonymized column and the fingerprint of its anonymized value.
 * A suite overrides getTable() with its configurable table.
 *
 * @property string $model_type
 * @property string $model_id
 * @property string $column
 * @property string $fingerprint
 */
abstract class Tombstone extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];
}
