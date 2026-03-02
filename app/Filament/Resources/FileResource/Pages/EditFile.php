<?php

namespace App\Filament\Resources\FileResource\Pages;

use App\Filament\Resources\FileResource;
use Filament\Resources\Pages\EditRecord;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderException;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class EditFile extends EditRecord
{
    protected static string $resource = FileResource::class;

    // دالة للتحقق من النص الفرنسي فقط
    protected function isFrenchOnly($text): bool
    {
        $text = trim($text);
        return $text !== '' && preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿŒœÆæÇç\'\-\s]+$/u', $text);
    }

    // دالة للتحقق من أن القيمة تاريخ صالح
    protected function isValidDate($date): bool
    {
        if (empty($date)) {
            return false;
        }
        return strtotime($date) !== false;
    }

    // نسخة أخرى: إذا كان الحقل فارغًا نعتبره مقبولًا
    protected function isValidDate1($date): bool
    {
        if (empty($date)) {
            return true;
        }
        return strtotime($date) !== false;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
            $path = storage_path('app/public/' . $data['path']); // أضف هذا السطر لتحديد مسار الملف


        try {
            $reader = IOFactory::createReaderForFile($path);
            $spreadsheet = $reader->load($path);
            $worksheet = $spreadsheet->getActiveSheet();
        } catch (ReaderException $e) {
            Notification::make()
                ->title('⚠️ فشل تحميل الملف')
                ->body('الملف المرفوع ليس من نوع Excel صالح (.xlsx أو .xls).')
                ->danger()
                ->persistent()
                ->send();

            throw new Halt();
        }
          if (auth()->user()->is_admin) {
            return $data;
        }


        // ✅ الأعمدة المطلوبة تمامًا
        $expectedHeaders = [
            'CPP', 'N_SS', 'NOM', 'PRENOM', 'DT_NAIS',
            'Dure1', 'Unite1', 'Tri_01',
            'Dure2', 'Unite2', 'Tri_02',
            'Dure3', 'Unite3', 'Tri_03',
            'Dure4', 'Unite4', 'Tri_04',
            'DT_Entree', 'DT_Sortie', 'TOTAL'
        ];

        $headers = [];
        foreach ($worksheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $headers[] = trim((string) $cell->getValue());
            }
        }

        // تحقق من التطابق التام
        if ($headers !== $expectedHeaders) {
            $missing = array_diff($expectedHeaders, $headers);
            $extra = array_diff($headers, $expectedHeaders);

            $message = '⚠️ تنسيق الأعمدة غير مطابق للمطلوب.';
            if (!empty($missing)) {
                $message .= "\nالأعمدة الناقصة: " . implode(', ', $missing);
            }
            if (!empty($extra)) {
                $message .= "\nأعمدة غير متوقعة: " . implode(', ', $extra);
            }

            Notification::make()
                ->title('فشل التحقق من تنسيق الملف')
                ->body(nl2br($message))
                ->danger()
                ->persistent()
                ->send();

            throw new Halt();
        }

        // ✅ تحقق من صحة البيانات داخل الجدول
        $rows = $worksheet->toArray(null, true, true, true);
        $errors = [];

        for ($i = 2; $i <= count($rows); $i++) {
            $row = $rows[$i];

            // التحقق من N_SS
            if (empty($row['B']) || !is_numeric(str_replace(' ', '', $row['B']))) {
                $errors[] = "الصف $i: N_SS يجب أن يكون رقمًا وغير فارغ.";
            }

            // التحقق من NOM و PRENOM
            if (!$this->isFrenchOnly($row['C']) || is_numeric($row['C'])) {
                $errors[] = "الصف $i: NOM يجب أن يكون نصًا فرنسيًا وغير فارغ.";
            }
            if (!$this->isFrenchOnly($row['D']) || is_numeric($row['D'])) {
                $errors[] = "الصف $i: PRENOM يجب أن يكون نصًا فرنسيًا وغير فارغ.";
            }

            // التحقق من التواريخ
            if (!$this->isValidDate($row['E'])) {
                $errors[] = "الصف $i: DT_NAIS يجب أن يكون تاريخًا صالحًا.";
            }
            if (!$this->isValidDate($row['Q'])) {
                $errors[] = "الصف $i: DT_Entree يجب أن يكون تاريخًا صالحًا.";
            }
            if (!$this->isValidDate1($row['R'])) {
                $errors[] = "الصف $i: DT_Sortie يجب أن يكون تاريخًا صالحًا.";
            }

            // التحقق من Dure1 إلى Dure4 ≤ 90 و UniteX ليست رقم
            $dureCols = ['F', 'I', 'L', 'O'];
            $uniteCols = ['G', 'J', 'M', 'P'];

            foreach (range(0, 3) as $j) {
                $dure = floatval(str_replace(',', '.', $row[$dureCols[$j]] ?? 0));
                $unite = trim($row[$uniteCols[$j]] ?? '');

                if ($dure > 90) {
                    $errors[] = "الصف $i: Dure" . ($j + 1) . " أكبر من 90.";
                }

                if (is_numeric($unite)) {
                    $errors[] = "الصف $i: Unite" . ($j + 1) . " يجب أن يكون نصًا (مثل 'j').";
                }
            }
        }

        if (count($errors) > 0) {
            Notification::make()
                ->title('❌ أخطاء في محتوى الملف')
                ->body(implode("\n", array_slice($errors, 0, 5)) . (count($errors) > 5 ? "\n..." : ''))
                ->danger()
                ->persistent()
                ->send();

            throw new Halt();
        }

        return $data;
    }
}
