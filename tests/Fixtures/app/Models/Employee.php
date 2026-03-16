<?php

namespace Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $manager_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $job_title
 * @property Carbon|null $hired_at
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Employee[] $directReports
 * @property-read int $direct_reports_count
 * @property Employee|null $manager
 * @ownLinks manager_id,\Fixtures\App\Models\Employee,id
 * @foreignLinks id,\Fixtures\App\Models\Employee,manager_id
 */
class Employee extends Model
{
    protected $fillable = [
        'team_id',
        'manager_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'job_title',
        'hired_at',
        'status',
    ];

    protected $casts = [
        'hired_at' => 'date',
    ];

    /** @return BelongsTo<Employee, $this> */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /** @return HasMany<Employee, $this> */
    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }
}
