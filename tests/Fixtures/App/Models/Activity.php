<?php

namespace Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $event_type
 * @property string $description
 */
class Activity extends Model
{
    protected $table = 'activity';
}
