import os
import pandas as pd
import tkinter as tk
from tkinter import filedialog, messagebox, simpledialog, Toplevel, Scrollbar, Text, RIGHT, Y, BOTH, END


def select_folder():
    folder = filedialog.askdirectory(title="📁 اختر مجلد الملفات")
    folder_var.set(folder)


def show_column_selection_dialog(filename, columns_text):
    dialog = Toplevel()
    dialog.title(f"📌 الأعمدة في {filename}")
    dialog.geometry("600x400")

    text = Text(dialog, wrap="word")
    text.insert(END, columns_text)
    text.pack(expand=True, fill=BOTH, padx=10, pady=10)

    scrollbar = Scrollbar(dialog, command=text.yview)
    text.config(yscrollcommand=scrollbar.set)
    scrollbar.pack(side=RIGHT, fill=Y)

    entry_label = tk.Label(dialog, text="أدخل أرقام الأعمدة مثل: 1,2")
    entry_label.pack(pady=(10, 0))
    entry = tk.Entry(dialog, width=40)
    entry.pack(pady=5)

    result = {'value': None}

    def submit():
        result['value'] = entry.get()
        dialog.destroy()

    submit_btn = tk.Button(dialog, text="موافق", command=submit)
    submit_btn.pack(pady=10)

    dialog.transient(root)
    dialog.grab_set()
    root.wait_window(dialog)

    return result['value']


def merge_excels():
    input_folder = folder_var.get()
    output_file = file_name_var.get().strip()

    if not input_folder or not output_file:
        messagebox.showerror("خطأ", "يرجى تحديد المجلد واسم الملف الناتج.")
        return

    excel_exts = ('.xls', '.xlsx', '.xlsm')
    merged_columns = []
    column_count = None
    sources = []

    for fn in os.listdir(input_folder):
        if not fn.lower().endswith(excel_exts):
            continue

        path = os.path.join(input_folder, fn)
        try:
            sheet_names = pd.ExcelFile(path).sheet_names
        except Exception as e:
            messagebox.showwarning("خطأ", f"لا يمكن قراءة الملف {fn}:\n{e}")
            continue

        try:
            sheet_list_text = '\n'.join(f"[{i}] {s}" for i, s in enumerate(sheet_names))
            full_prompt = f"🗂️ الأوراق المتاحة في {fn}:\n{sheet_list_text}\n\nاكتب رقم الورقة (افتراضي 0):"
            sheet_index = simpledialog.askinteger("اختيار الورقة", full_prompt, initialvalue=0)
        except Exception as e:
            messagebox.showwarning("خطأ", f"تعذر عرض قائمة الأوراق:\n{e}")
            continue

        if sheet_index is None:
            continue

        try:
            df_raw = pd.read_excel(path, sheet_name=sheet_names[sheet_index], header=None)
        except Exception as e:
            messagebox.showwarning("خطأ", f"لا يمكن قراءة الورقة في الملف {fn}:\n{e}")
            continue

        try:
            col_preview = "\n".join([
                f"[{i}] = {str(df_raw.iloc[0, i]) if i < df_raw.shape[1] else 'NaN'}"
                for i in range(df_raw.shape[1])
            ])
        except Exception as e:
            messagebox.showwarning("خطأ", f"فشل عرض الأعمدة:\n{e}")
            continue

        choice = show_column_selection_dialog(fn, col_preview)
        if not choice:
            continue

        try:
            indices = [int(x.strip()) for x in choice.split(',') if x.strip().isdigit()]
            if column_count is None:
                column_count = len(indices)
                merged_columns = [[] for _ in range(column_count)]
            elif len(indices) != column_count:
                messagebox.showerror("خطأ", "يجب اختيار نفس عدد الأعمدة في كل ملف.")
                continue

            sub = df_raw.iloc[1:, indices].copy()
            sub = sub.reset_index(drop=True)

            for i in range(column_count):
                merged_columns[i].extend(sub.iloc[:, i].tolist())

            sources.extend([fn] * sub.shape[0])
        except Exception as e:
            messagebox.showerror("خطأ في الأعمدة", f"{e}")
            continue

    if merged_columns:
        data = {"📁_المصدر": sources}
        for i, col_data in enumerate(merged_columns):
            data[f"العمود {i + 1}"] = col_data

        result_df = pd.DataFrame(data)
        if not output_file.lower().endswith(".xlsx"):
            output_file += ".xlsx"

        try:
            result_df.to_excel(output_file, index=False)
            messagebox.showinfo("نجاح", f"✅ تم حفظ الملف: {output_file}")
        except Exception as e:
            messagebox.showerror("خطأ في الحفظ", str(e))
    else:
        messagebox.showinfo("تنبيه", "⚠️ لم يتم دمج أي بيانات.")


# واجهة المستخدم
root = tk.Tk()
root.title("دمج أعمدة من ملفات Excel")
root.geometry("500x250")

folder_var = tk.StringVar()
file_name_var = tk.StringVar()

# عناصر الواجهة
tk.Label(root, text="📁 مجلد الملفات:").pack(pady=5)
tk.Entry(root, textvariable=folder_var, width=60).pack()
tk.Button(root, text="استعراض...", command=select_folder).pack(pady=5)

tk.Label(root, text="💾 اسم الملف الناتج:").pack(pady=5)
tk.Entry(root, textvariable=file_name_var, width=60).pack()

tk.Button(root, text="🔄 ابدأ الدمج", command=merge_excels, bg="#4CAF50", fg="white", padx=10, pady=5).pack(pady=15)

root.mainloop()
