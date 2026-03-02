<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FileResource\Pages;
use App\Models\Fileadmin;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Illuminate\Database\Eloquent\Collection;

class FileadminResource extends Resource
{
    protected static ?string $model = Fileadmin::class;

    protected static ?string $navigationLabel = 'المستخدمين';
    protected static ?string $pluralLabel = 'المستخدمين';
    protected static ?string $modelLabel = 'مستخدم';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->email()
                    ->required(),

                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => \Hash::make($state))
                    ->required(fn (string $context) => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state)),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->date()
                    ->label('تاريخ الإنشاء'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('mergeSelected')
                        ->label('دمج الملفات المحددة')
                        ->icon('heroicon-o-document-text')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (Collection $records) {
                            // كود الدمج الحقيقي هنا لو أردت
                            \Filament\Notifications\Notification::make()
                                ->title('تم دمج الملفات بنجاح')
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFiles::route('/'),
            'create' => Pages\CreateFile::route('/create'),
            'edit'   => Pages\EditFile::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }
}
