<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Filament\Resources\ArticleResource\RelationManagers;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;



class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
  protected static ?string $navigationLabel = 'الاخبار';
    protected static ?string $pluralLabel = 'الاخبار';
        protected static ?string $modelLabel = 'خبر';
       public static function form(Form $form): Form
{
    return $form->schema([
        TextInput::make('title')->label('عنوان المقال')->required(),
        FileUpload::make('image')
            ->label('صورة المقال')
            ->directory('uploads/articles')
            ->visibility('public')
            ->image()
            ->required(),
        Forms\Components\Textarea::make('content')
            ->label('محتوى المقال')
            ->required(),
    ]);

    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\ImageColumn::make('image')
                ->label('صورة المقال')
                ->getStateUsing(function ($record) {
                    return $record->image
                        ? asset('storage/app/public/uploads/articles/' . $record->image)
                        : null;
                }),

            Tables\Columns\TextColumn::make('title')
                ->label('عنوان المقال')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('user.name')
                ->label('صاحب المقال')
                ->searchable()
                ->visible(fn () => auth()->user()?->is_admin),

            Tables\Columns\TextColumn::make('created_at')
                ->label('تاريخ الرفع')
                ->dateTime('d/m/Y H:i')
                ->sortable(),

            Tables\Columns\IconColumn::make('download')
                ->label('تحميل')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(fn ($record) => asset('storage/uploads/articles/' . $record->image))
                ->visible(fn () => auth()->user()?->can('download', \App\Models\File::class)),
        ])
        ->actions([
           Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
