<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'name',
        'path',
        'user_id',   // صاحب الملف
        'sent_to',   // المرسل إليه
    ];

    /**
     * صاحب الملف (الذي رفعه).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }



    /**
     * المستخدم الذي أُرسل له الملف.
     */
    public function sentToUser()
    {
        return $this->belongsTo(User::class, 'sent_to');
    }

    /**
     * المستخدمين الذين شُورك معهم الملف (علاقة Many-to-Many).
     */
    public function sharedWithUsers()
    {
        return $this->belongsToMany(User::class, 'file_user', 'file_id', 'user_id');
    }
}
