<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
protected static ?string $navigationLabel = 'المستخدمين';
protected static ?string $pluralLabel = 'المستخدمين';
protected static ?string $modelLabel = 'مستخدم';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
    ->schema([
        TextInput::make('name')
            ->label('الاسم')
            ->required(),

        TextInput::make('email')
            ->label('البريد الإلكتروني')
            ->email()
            ->required(),

        TextInput::make('password')
        
        ->label('كلمة المرور')
        ->password()
        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
        ->required(fn (string $context) => $context === 'create')
        ->dehydrated(fn ($state) => filled($state)),


        \Filament\Forms\Components\Toggle::make('is_admin')
            ->label('مشرف؟')
            ->default(false),
    ]);}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
               TextColumn::make('name')->label('الاسم')->searchable(),
    TextColumn::make('email')->label('البريد الإلكتروني')->searchable(),
    TextColumn::make('created_at')->date()->label('تاريخ الإنشاء'),
        ])
            ->filters([
                //
            ])
               ->searchable() 
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    public static function shouldRegisterNavigation(): bool
{
    return auth()->user()?->is_admin;
}
public static function canAccess(): bool
{
    return auth()->user()?->is_admin; // أو حسب الدور
}
}
