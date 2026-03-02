<?php

namespace App\Policies;

use App\Models\File;
use App\Models\User;

class FilePolicy
{
    /**
     * المستخدم يقدر يشوف قائمة الملفات:
     * - الأدمن يشوف الكل
     * - المستخدم العادي يشوف فقط ملفاته أو الملفات المرسلة له
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * المستخدم يقدر يشوف ملف محدد
     */
    public function view(User $user, File $file): bool
    {
        return $user->is_admin === 1 
            || $file->user_id === $user->id 
            || $file->sent_to === $user->id;
    }

    /**
     * كل المستخدمين يقدروا يرفعوا ملفات
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * يقدر يعدل:
     * - الأدمن
     * - صاحب الملف نفسه
     */
    public function update(User $user, File $file): bool
    {
        return $user->is_admin === 1 || $file->user_id === $user->id;
    }

    /**
     * يقدر يحذف:
     * - الأدمن فقط
     */
    public function delete(User $user, File $file): bool
    {
        return $user->is_admin === 1;
    }

    /**
     * الحذف الجماعي → الأدمن فقط
     */
    public function deleteAny(User $user): bool
    {
        return $user->is_admin === 1;
    }

    public function restore(User $user, File $file): bool
    {
        return $user->is_admin === 1;
    }

    public function forceDelete(User $user, File $file): bool
    {
        return $user->is_admin === 1;
    }
}
