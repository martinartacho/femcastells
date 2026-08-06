<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpItem extends Model
{
    protected $fillable = ['category', 'question', 'answer', 'sort', 'visible'];

    protected $casts = [
        'visible' => 'boolean',
        'sort'    => 'integer',
    ];
}
