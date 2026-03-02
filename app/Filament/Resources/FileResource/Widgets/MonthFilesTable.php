<?php

namespace App\Filament\Resources\FileResource\Widgets;

use App\Models\MonthPaper;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MonthFilesTable extends BaseWidget
{
   public static function canView(): bool
{
    return auth()->user()?->is_admin;
}


    public function table(Table $table): Table
    {
        return $table
            ->query(MonthPaper::query())
            ->columns([
                Tables\Columns\TextColumn::make('file1')
                    ->label('الملف الأول')
                    ->url(fn ($record) => asset('storage/uploads/month/' . $record->file1))
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn () => 'تحميل'),

                Tables\Columns\TextColumn::make('file2')
                    ->label('الملف الثاني')
                    ->url(fn ($record) => asset('storage/uploads/month/' . $record->file2))
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn () => 'تحميل'),

                Tables\Columns\TextColumn::make('file3')
                    ->label('الملف الثالث')
                    ->url(fn ($record) => asset('storage/uploads/month/' . $record->file3))
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn () => 'تحميل'),

                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime(),
            ]);
    }
}
