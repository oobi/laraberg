<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Oobi\Laraberg\Traits\RendersContent;

class Post extends Model
{
    use HasFactory, RendersContent;

    protected $guarded = ['id'];

    protected $contentColumn = 'content';
}
