<?php

namespace JeffersonGoncalves\Privacy\Consent;

/**
 * Base of every suite's ConsentRecorded event, fired after each grant or revoke.
 */
abstract class ConsentRecorded
{
    public function __construct(public ConsentRecord $record) {}
}
