<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    /**
     * الحقول القابلة للتعبئة.
     */
    protected $fillable = [
        'title',
        'content',
        'image',
        'user_id',
    ];
protected $searchable = [
    'title',
    'content',
];

public function toSearchableArray()
{
    return [
        'title' => $this->title,
        'content' => strip_tags($this->content),
    ];
}
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
