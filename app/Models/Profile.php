<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    protected $fillable = ['colla_id', 'name', 'permissions'];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
        ];
    }

    public static function defaultPermissions(): array
    {
        return [
            'castellers'   => ['read' => false, 'write' => false],
            'esdeveniments' => ['read' => false, 'write' => false],
            'pissarra'     => ['read' => false, 'write' => false],
            'plantilles'   => ['read' => false, 'write' => false],
            'noticies'     => ['read' => false, 'write' => false],
            'tags'         => ['read' => false, 'write' => false],
            'config'       => ['read' => false, 'write' => false],
            'generador'    => ['read' => false, 'write' => false],
            'botiga'       => ['read' => false, 'write' => false],
        ];
    }

    public function can(string $area, string $level = 'read'): bool
    {
        return (bool) ($this->permissions[$area][$level] ?? false);
    }

    public function colla(): BelongsTo
    {
        return $this->belongsTo(Colla::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
