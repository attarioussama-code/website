<?php

namespace App\Filament\User\Widgets;

use App\Models\Article;
use Filament\Widgets\Widget;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

class LatestArticles extends Widget
{
    protected static string $view = 'filament.user.widgets.latest-articles';
 

    /**
     * جلب أحدث المقالات (مثلاً 5 مقالات)
     */
    protected function getViewData(): array
    {
        return [
            'articles' => Article::query()
                ->latest()
                ->take(6)
                ->get()
        ];
    }

    public static function getColumns(): int | string | array
{
    return 'full'; // أو يمكنك كتابة: return 2; لعمودين أو 'full' رض الكاملللع
}
}
