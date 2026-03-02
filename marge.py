import os
import pandas as pd

# 🟡 استدعاء معلومات من المستخدم
input_folder = input("📁 مجلد الملفات: ").strip()
output_file = input("💾 اسم الملف الناتج: ").strip()
excel_exts = ('.xls', '.xlsx', '.xlsm')

# 🟡 قائمة لكل عمود موحد (عمود 1، عمود 2، ...)
merged_columns = []
column_count = None

# 🟡 بدء التكرار على كل ملف
for fn in os.listdir(input_folder):
    if not fn.lower().endswith(excel_exts):
        continue

    path = os.path.join(input_folder, fn)
    print(f"\n📄 الملف: {fn}")

    try:
        sheet_names = pd.ExcelFile(path).sheet_names
    except Exception as e:
        print(f"⛔ خطأ في قراءة الملف: {e}")
        continue

    print("🗂️ الأوراق المتاحة:")
    for idx, name in enumerate(sheet_names):
        print(f" [{idx}] {name}")

    sheet_input = input("🔢 رقم الورقة المطلوبة (افتراضي 0): ").strip()
    sheet_index = int(sheet_input) if sheet_input else 0

    try:
        df_raw = pd.read_excel(path, sheet_name=sheet_names[sheet_index], header=None)
    except Exception as e:
        print(f"⛔ خطأ في قراءة الورقة: {e}")
        continue

    cols = df_raw.columns.tolist()
    print("📌 الأعمدة المتاحة:")
    for i in cols:
        try:
            val = df_raw.iloc[0, i]
        except:
            val = "NaN"
        print(f" [{i}] = {val}")

    choice = input("✅ اختر أرقام الأعمدة للفصل (مثلاً: 1,2): ").strip()
    if not choice:
        print("⛔ تم تجاوز الملف.")
        continue

    try:
        indices = [int(x.strip()) for x in choice.split(',')]
        if column_count is None:
            column_count = len(indices)
            # بدء عمود فارغ لكل حقل موحد
            merged_columns = [[] for _ in range(column_count)]
            sources = []
        elif len(indices) != column_count:
            print("⛔ يجب اختيار نفس عدد الأعمدة لكل ملف!")
            continue

        sub = df_raw.iloc[1:, indices].copy()  # إزالة أول صف (رؤوس الأعمدة)
        sub = sub.reset_index(drop=True)

        for i, col_index in enumerate(indices):
            merged_columns[i].extend(sub.iloc[:, i].tolist())

        sources.extend([fn] * sub.shape[0])  # تكرار اسم الملف بعدد الصفوف
    except Exception as e:
        print(f"⛔ خطأ في استخراج الأعمدة: {e}")
        continue

# 📤 حفظ الناتج
if merged_columns:
    data = {"📁_المصدر": sources}
    for i, col_data in enumerate(merged_columns):
        data[f"العمود {i+1}"] = col_data

    result_df = pd.DataFrame(data)
    if not output_file.lower().endswith('.xlsx'):
        output_file += '.xlsx'

    try:
        result_df.to_excel(output_file, index=False)
        print(f"\n✅ تم حفظ الملف بنجاح: {output_file}")
    except Exception as e:
        print(f"⛔ خطأ أثناء الحفظ: {e}")
else:
    print("\n⚠️ لم يتم دمج أي بيانات.")
