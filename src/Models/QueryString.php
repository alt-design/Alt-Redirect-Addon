<?php

namespace AltDesign\AltRedirect\Models;

use Illuminate\Database\Eloquent\Model;

class QueryString extends Model
{
    protected $table = 'alt_query_strings';

    protected $fillable = [
        'id',
        'query_string',
        'strip',
        'sites',
    ];

    protected $casts = [
        'strip' => 'boolean',
        'sites' => 'array',
    ];

    public $incrementing = false;

    protected $keyType = 'string';
}
