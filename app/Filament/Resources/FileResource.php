<?php

namespace App\Filament\Resources;

use App\Models\File;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\MultiSelect;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\ViewColumn;
use App\Filament\Resources\FileResource\Pages;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Illuminate\Support\Facades\Storage;

use Filament\Notifications\Notification;
class FileResource extends Resource
{
    protected static ?string $model = File::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-down';
    protected static ?string $navigationLabel = 'الملفات';
    protected static ?string $pluralLabel = 'الملفات';
    protected static ?string $modelLabel = 'ملف';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('اسم الملف')
                ->required(),

            FileUpload::make('path')
                ->label('الملف')
                  ->directory('uploads/files')
                ->visibility('public')
                ->required(),
                Select::make('sent_to')
                ->label('إرسال إلى مستخدم')
                ->options(User::query()->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable()
                ->visible(fn () => auth()->user()?->is_admin )
                ->default(null),



            MultiSelect::make('sharedWithUsers')
                ->label('مشاركة مع مستخدمين')
                ->relationship('sharedWithUsers', 'name')
                ->visible(fn () => auth()->user()?->is_admin ),
                 
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
                ->columns([
                Tables\Columns\TextColumn::make('name')->label('اسم الملف'),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الرفع')->dateTime(),
                Tables\Columns\TextColumn::make('user.name')
                ->label('صاحب الملف'),

                    



            ])

            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                ->visible(),
            ])
         ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                BulkAction::make('mergeSelected')
                ->visible(fn () => auth()->user()?->is_admin === 1)
                    ->label('دمج الملفات المحددة')
                    ->icon('heroicon-o-document-text')
                    ->action(function (Collection $records, $livewire) {
                        if ($records->isEmpty()) {
                            Notification::make()
                                ->title('لم يتم تحديد أي ملفات')
                                ->warning()
                                ->send();
                            return;
            }

            $mergedSpreadsheet = new Spreadsheet();
            $mergedSheet = $mergedSpreadsheet->getActiveSheet();
            $currentRow = 1;
            $isFirstFile = true;

            foreach ($records as $record) {
                $filePath = storage_path('app/public/' . $record->path);

                if (!file_exists($filePath)) {
                    continue;
                }

                $spreadsheet = IOFactory::load($filePath);
                $sheet = $spreadsheet->getActiveSheet();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                if ($isFirstFile) {
                    for ($row = 1; $row <= $highestRow; $row++) {
                        $rowData = $sheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, false);
                        $mergedSheet->fromArray($rowData[0], null, 'A' . $currentRow);
                        $currentRow++;
                    }
                    $isFirstFile = false;
                } else {
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $rowData = $sheet->rangeToArray("A{$row}:{$highestColumn}{$row}", null, true, false);
                        $mergedSheet->fromArray($rowData[0], null, 'A' . $currentRow);
                        $currentRow++;
                    }
                }
            }

            // احفظ الملف في مجلد uploads/files
            $timestamp = now()->format('Ymd_His');
            $mergedFileName = "merged_{$timestamp}.xlsx";
            $relativePath = "uploads/files/{$mergedFileName}";
            $savePath = storage_path("app/public/{$relativePath}");

            $writer = IOFactory::createWriter($mergedSpreadsheet, 'Xlsx');
            $writer->save($savePath);

            // احفظ في قاعدة البيانات
            $newFile = File::create([
                'name'    => "Merged File {$timestamp}",
                'path'    => $relativePath,
                'user_id' => auth()->id(),  // اربط الملف بالمستخدم الحالي
            ]);

            // أعد توجيه المستخدم مباشرة لتحميل الملف
            $downloadUrl = Storage::url($relativePath);
            return redirect($downloadUrl);

        })
        ->requiresConfirmation()
        ->deselectRecordsAfterCompletion(),
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
public static function canDelete($record): bool
{
    return auth()->user()->is_admin === 1;
}



public static function getEloquentQuery(): Builder
{
    $user = auth()->user();

    // لو الأدمن يشوف الكل
    if ($user->is_admin) {
        return File::query();
    }

    // المستخدم العادي
     return parent::getEloquentQuery()
        ->where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('sent_to', $user->id);
        });
}
}

