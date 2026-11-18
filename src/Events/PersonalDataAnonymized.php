<?php

namespace JeffersonGoncalves\Privacy\Events;

use Illuminate\Database\Eloquent\Model;

/**
 * Base of every suite's PersonalDataAnonymized event, fired by anonymizePersonalData() after saving.
 * $columns lists the columns changed (Keep columns excluded).
 */
abstract class PersonalDataAnonymized
{
    /**
     * @param  list<string>  $columns
     */
    public function __construct(public Model $model, public array $columns) {}
}
