<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Illuminate\Support\Facades\Storage;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (!empty($data['image'])) {
            $imagePath = 'uploads/articles/' . $data['image'];

            if (Storage::disk('public')->exists($imagePath)) {
                $this->resizeImage($imagePath);
            }

            // نحافظ على الاسم نفسه لأنك تحفظ فقط الاسم في قاعدة البيانات
            $data['image'] = $data['image'];
        }

        return $data;
    }

    private function resizeImage($imagePath): void
    {
        $manager = new ImageManager(new GdDriver());

        $image = $manager->read(Storage::disk('public')->path($imagePath));

        $maxWidth = 500;
        $maxHeight = 550;

        $image->scale(width: $maxWidth, height: $maxHeight);

        $image->save();
        
    }
}
