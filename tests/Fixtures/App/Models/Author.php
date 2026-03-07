<?php

namespace Fixtures\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string|null $bio
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Author extends Model
{
    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
