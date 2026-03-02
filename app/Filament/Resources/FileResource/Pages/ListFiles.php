<?php

namespace App\Filament\Resources\FileResource\Pages;

use App\Filament\Resources\FileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use App\Models\MonthPaper;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Pages\Actions\Action;
use App\Filament\Resources\FileResource\Widgets\MonthFilesTable;
use Illuminate\Support\Facades\Storage; // ← أضف هذا السطر
class ListFiles extends ListRecords
{
    protected static string $resource = FileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('رفع ملفات ورقة الأشهر')
    ->label('رفع ملفات ورقة الأشهر')
    ->modalHeading('رفع ملفات ورقة الأشهر')
    ->requiresConfirmation() // ✅ إضافة نافذة تأكيد
    ->modalDescription('سيتم حذف جميع الملفات القديمة قبل رفع الملفات الجديدة. هل أنت متأكد أنك تريد المتابعة؟')
    ->form([
        FileUpload::make('month_file1')
            ->label('الملف الاساتذة')
            ->directory('uploads/month')
            ->disk('public')
            ->maxSize(20240)
            ->rules(['max:20240'])
            ->required(),
        FileUpload::make('month_file2')
            ->label('الملف العمال المتعاقدين')
            ->directory('uploads/month')
            ->disk('public')
             ->maxSize(20240)
             ->rules(['max:20240'])
            ->required(),
        FileUpload::make('month_file3')
            ->label('الملف العمال الدائمين')
            ->directory('uploads/month')
            ->disk('public')
             ->maxSize(20240)
             ->rules(['max:20240'])
            ->required(),
    
                ])
                ->action(function (array $data) {
                     

    $oldPapers = \App\Models\MonthPaper::all();
    if( $oldPapers != null){
  foreach ($oldPapers as $paper) {
        if ($paper->file1) {
            Storage::disk('public')->delete( $paper->file1);
        }
        if ($paper->file2) {
            Storage::disk('public')->delete( $paper->file2);
        }
        if ($paper->file3) {
            Storage::disk('public')->delete($paper->file3);
        }
    }
     if( \App\Models\MonthPaper::query()->delete()){
    
      MonthPaper::create([
                        'file1' => $data['month_file1'],
                        'file2' => $data['month_file2'],
                        'file3' => $data['month_file3'],
                    ]);

                   
   };

    } 
  MonthPaper::create([
                        'file1' => $data['month_file1'],
                        'file2' => $data['month_file2'],
                        'file3' => $data['month_file3'],
                    ]);
    
                
    // احذف السجلات القديمة
  
 \Filament\Notifications\Notification::make()
                        ->title('تم رفع الملفات بنجاح')
                        ->success()
                        ->send();
                })
                ->visible(fn () => auth()->user()?->is_admin),
        ];
                  
        
    }
    
protected function getHeaderWidgets(): array
{
    return [
        MonthFilesTable::class,
    ];
}

    protected function getTableQuery(): Builder
{
    if (auth()->user()->is_admin) {
        return parent::getTableQuery();
    }

    // يشوف ملفاته أو الملفات المرسلة له
    return parent::getTableQuery()
        ->where(function ($q) {
            $q->where('user_id', auth()->id())
              ->orWhere('sent_to', auth()->id());
        });
}

protected function getTableActions(): array
{
    return [
        Actions\ViewAction::make(), // الكل يقدر يشوف
        Actions\EditAction::make()
            ->visible(fn ($record) => $record->user_id === auth()->id()), // فقط ملفاته
        Actions\DeleteAction::make()
            ->visible(fn ($record) => $record->user_id === auth()->id()), // فقط ملفاته
    ];
}


}
