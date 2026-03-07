<?php

namespace Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property Carbon|null $sent_at
 * @property Carbon|null $delivered_at
 * @property string|null $delivery_status
 * @property string|null $delivery_reason
 */
class Email extends Model {}
