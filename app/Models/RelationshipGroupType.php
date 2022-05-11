<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RelationshipGroupType extends Model
{
    use HasFactory;

    protected $table = 'relationship_group_types';

    /**
     * Possible types.
     */
    const TYPE_FAMILY = 'family';
    const TYPE_LOVE = 'love';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_id',
        'name',
        'type',
        'can_be_deleted',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'can_be_deleted' => 'boolean',
    ];

    /**
     * Get the account associated with the relationship type.
     *
     * @return BelongsTo
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the account associated with the relationship type.
     *
     * @return HasMany
     */
    public function types()
    {
        return $this->hasMany(RelationshipType::class);
    }
}