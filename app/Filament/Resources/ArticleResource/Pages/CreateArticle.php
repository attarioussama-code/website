<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Resources\Pages\CreateRecord;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Illuminate\Support\Facades\Storage;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        if (isset($data['image'])) {
            // استخراج اسم الملف فقط بدون المسار
            $data['image'] = basename($this->resizeImage($data['image']));
        }

        return $data;
    }

    private function resizeImage($imagePath): string
    {
        $manager = new ImageManager(new GdDriver());

        // مسار كامل للملف في التخزين
        $fullPath = Storage::disk('public')->path($imagePath);

        // قراءة الصورة
        $image = $manager->read($fullPath);

        // تغيير الحجم
        $maxWidth = 500;
        $maxHeight = 550;
        $image->scale(width: $maxWidth, height: $maxHeight);

        // حفظ الصورة بعد التعديل
        $image->save();

        return $imagePath;
    }
}
