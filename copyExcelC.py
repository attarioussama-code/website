import warnings
import os
import pandas as pd
import tkinter as tk
from tkinter import filedialog, messagebox, simpledialog, Toplevel, ttk
from tkinter.scrolledtext import ScrolledText

class ExcelMerger:
    def __init__(self, root):
        self.root = root
        self.root.title("أداة دمج ملفات إكسل المتقدمة")
        self.root.geometry("800x600")
        
        # تجاهل التحذيرات غير الهامة
        warnings.filterwarnings('ignore', category=UserWarning)
        warnings.filterwarnings('ignore', category=FutureWarning)
        
        # متغيرات الواجهة
        self.folder_var = tk.StringVar()
        self.output_file_var = tk.StringVar(value="الملف_المدمج.xlsx")
        self.merge_method_var = tk.StringVar(value="vertical")
        self.header_option_var = tk.StringVar(value="auto")
        self.engine_var = tk.StringVar(value="auto")
        
        # إنشاء عناصر الواجهة
        self.create_widgets()
    
    def create_widgets(self):
        # إطار الإدخال
        input_frame = tk.LabelFrame(self.root, text="إعدادات الإدخال", padx=10, pady=10)
        input_frame.pack(fill="x", padx=10, pady=5)
        
        # اختيار المجلد
        tk.Label(input_frame, text="📁 مجلد الملفات:").grid(row=0, column=0, sticky="w")
        tk.Entry(input_frame, textvariable=self.folder_var, width=60).grid(row=0, column=1, padx=5)
        tk.Button(input_frame, text="استعراض...", command=self.select_folder).grid(row=0, column=2)
        
        # إطار خيارات الدمج
        merge_frame = tk.LabelFrame(self.root, text="خيارات الدمج", padx=10, pady=10)
        merge_frame.pack(fill="x", padx=10, pady=5)
        
        # طريقة الدمج
        tk.Label(merge_frame, text="طريقة الدمج:").grid(row=0, column=0, sticky="w")
        ttk.Combobox(merge_frame, textvariable=self.merge_method_var, 
                    values=["vertical", "horizontal"], state="readonly").grid(row=0, column=1, sticky="w", padx=5)
        
        # خيارات الرأس
        tk.Label(merge_frame, text="خيارات الرأس:").grid(row=1, column=0, sticky="w")
        ttk.Combobox(merge_frame, textvariable=self.header_option_var, 
                    values=["auto", "no_header", "custom"], state="readonly").grid(row=1, column=1, sticky="w", padx=5)
        
        # محرك القراءة
        tk.Label(merge_frame, text="محرك القراءة:").grid(row=2, column=0, sticky="w")
        ttk.Combobox(merge_frame, textvariable=self.engine_var, 
                    values=["auto", "openpyxl", "xlrd", "odf"], state="readonly").grid(row=2, column=1, sticky="w", padx=5)
        
        # إطار الإخراج
        output_frame = tk.LabelFrame(self.root, text="إعدادات الإخراج", padx=10, pady=10)
        output_frame.pack(fill="x", padx=10, pady=5)
        
        # اسم ملف الإخراج
        tk.Label(output_frame, text="💾 اسم الملف الناتج:").grid(row=0, column=0, sticky="w")
        tk.Entry(output_frame, textvariable=self.output_file_var, width=60).grid(row=0, column=1, padx=5)
        
        # زر البدء
        tk.Button(self.root, text="🔄 بدء عملية الدمج", command=self.merge_files, 
                 bg="#4CAF50", fg="white", font=('Arial', 12), padx=20, pady=10).pack(pady=20)
    
    def select_folder(self):
        folder = filedialog.askdirectory(title="📁 اختر مجلد الملفات")
        if folder:
            self.folder_var.set(folder)
    
    def get_sheets(self, file_path):
        """الحصول على قائمة الصفحات مع معالجة الأخطاء"""
        try:
            if file_path.endswith('.xls'):
                engine = 'xlrd'
            elif file_path.endswith('.xlsx'):
                engine = 'openpyxl'
            elif file_path.endswith('.xlsm'):
                engine = 'openpyxl'
            elif file_path.endswith('.ods'):
                engine = 'odf'
            else:
                engine = self.engine_var.get() if self.engine_var.get() != "auto" else None
            
            with pd.ExcelFile(file_path, engine=engine) as xls:
                return xls.sheet_names
        except Exception as e:
            messagebox.showerror("خطأ", f"لا يمكن قراءة الملف:\n{str(e)}")
            return None
    
    def show_sheet_selection(self, filename):
        """يعرض نافذة اختيار الصفحة من قائمة منسدلة"""
        file_path = os.path.join(self.folder_var.get(), filename)
        sheets = self.get_sheets(file_path)
        if not sheets:
            return None
        
        dialog = Toplevel(self.root)
        dialog.title(f"اختيار الصفحة - {filename}")
        dialog.geometry("400x200")
        
        tk.Label(dialog, text="اختر الصفحة المطلوبة:").pack(pady=10)
        
        sheet_var = tk.StringVar(value=sheets[0])
        sheet_dropdown = ttk.Combobox(dialog, textvariable=sheet_var, values=sheets, state="readonly")
        sheet_dropdown.pack(pady=5)
        
        result = {'sheet': None}
        
        def submit():
            result['sheet'] = sheet_var.get()
            dialog.destroy()
        
        tk.Button(dialog, text="موافق", command=submit).pack(pady=10)
        
        dialog.transient(self.root)
        dialog.grab_set()
        self.root.wait_window(dialog)
        
        return result['sheet']
    
    def get_custom_header_row(self, df):
        """الحصول على رقم سطر الرأس من المستخدم - بواجهة أوضح"""
        dialog = Toplevel(self.root)
        dialog.title("تحديد سطر الرأس")
        dialog.geometry("700x400")
        
        tk.Label(dialog, text="يرجى اختيار رقم السطر الذي يحتوي على رؤوس الأعمدة.\nملاحظة: الترقيم يبدأ من 0 (أي الصف الأول رقمه 0).", font=('Arial', 10)).pack(pady=5)
        
        # عرض عينة البيانات باستخدام Treeview
        frame = tk.Frame(dialog)
        frame.pack(fill="both", expand=True, padx=10, pady=5)
        
        tree = ttk.Treeview(frame, show='headings')
        tree.pack(side="left", fill="both", expand=True)
        
        # إضافة Scrollbar عمودي
        scrollbar = ttk.Scrollbar(frame, orient="vertical", command=tree.yview)
        scrollbar.pack(side="right", fill="y")
        tree.configure(yscrollcommand=scrollbar.set)
        
        # تعريف الأعمدة في Treeview
        tree["columns"] = list(df.columns)
        for col in df.columns:
            tree.heading(col, text=str(col))
            tree.column(col, width=120, anchor="w")
        
        # إضافة أول 10 صفوف (أو أقل)
        sample_df = df.head(10)
        for idx, row in sample_df.iterrows():
            values = [str(row[col]) if pd.notna(row[col]) else "" for col in df.columns]
            tree.insert("", "end", values=values)
        
        # حقل إدخال رقم السطر
        tk.Label(dialog, text="رقم السطر:").pack(pady=5)
        entry = tk.Entry(dialog)
        entry.pack(pady=5)
        
        result = {'header_row': 0}
        
        def submit():
            try:
                header_row = int(entry.get())
                if header_row < 0 or header_row >= len(df):
                    messagebox.showerror("خطأ", "رقم السطر غير صالح")
                    return
                result['header_row'] = header_row
                dialog.destroy()
            except ValueError:
                messagebox.showerror("خطأ", "يجب إدخال رقم صحيح")
        
        tk.Button(dialog, text="موافق", command=submit).pack(pady=10)
        
        dialog.transient(self.root)
        dialog.grab_set()
        self.root.wait_window(dialog)
        
        return result['header_row']

    def read_excel_safe(self, file_path, sheet_name):
        """قراءة ملف Excel مع معالجة الأخطاء"""
        try:
            if file_path.endswith('.xls'):
                engine = 'xlrd'
            elif file_path.endswith(('.xlsx', '.xlsm')):
                engine = 'openpyxl'
            elif file_path.endswith('.ods'):
                engine = 'odf'
            else:
                engine = self.engine_var.get() if self.engine_var.get() != "auto" else None
            
            header = 0 if self.header_option_var.get() == "auto" else None
            
            with warnings.catch_warnings():
                warnings.simplefilter("ignore")
                
                # قراءة البيانات بدون رأس أولاً إذا كان الخيار custom
                if self.header_option_var.get() == "custom":
                    df = pd.read_excel(file_path, sheet_name=sheet_name, header=None, engine=engine)
                    
                    # عرض البيانات للمستخدم وطلب تحديد سطر الرأس
                    header_row = self.get_custom_header_row(df)
                    
                    # إعادة قراءة البيانات مع سطر الرأس المحدد
                    df = pd.read_excel(file_path, sheet_name=sheet_name, header=header_row, engine=engine)
                else:
                    df = pd.read_excel(file_path, sheet_name=sheet_name, header=header, engine=engine)
                
                # معالجة البيانات الفارغة
                df = df.dropna(how='all')
                df = df.reset_index(drop=True)
                
                return df
        except Exception as e:
            messagebox.showerror("خطأ", f"لا يمكن قراءة الملف {file_path}:\n{str(e)}")
            return None
    
    def show_column_selection(self, filename, df):
        """عرض نافذة اختيار الأعمدة"""
        dialog = Toplevel(self.root)
        dialog.title(f"اختيار الأعمدة - {filename}")
        dialog.geometry("900x600")
        
        # إطار المعلومات
        info_frame = tk.Frame(dialog)
        info_frame.pack(fill="x", padx=10, pady=5)
        
        tk.Label(info_frame, text=f"الملف: {filename}", font=('Arial', 10, 'bold')).pack(side="left")
        tk.Label(info_frame, text=f"عدد الأعمدة: {len(df.columns)}", font=('Arial', 10)).pack(side="left", padx=10)
        tk.Label(info_frame, text=f"عدد الصفوف: {len(df)}", font=('Arial', 10)).pack(side="left")
        
        # إطار عرض البيانات
        data_frame = tk.Frame(dialog)
        data_frame.pack(fill="both", expand=True, padx=10, pady=5)
        
        # شجرة لعرض البيانات
        tree = ttk.Treeview(data_frame)
        
        # شريط التمرير
        vsb = ttk.Scrollbar(data_frame, orient="vertical", command=tree.yview)
        hsb = ttk.Scrollbar(data_frame, orient="horizontal", command=tree.xview)
        tree.configure(yscrollcommand=vsb.set, xscrollcommand=hsb.set)
        
        tree.grid(row=0, column=0, sticky="nsew")
        vsb.grid(row=0, column=1, sticky="ns")
        hsb.grid(row=1, column=0, sticky="ew")
        
        data_frame.grid_rowconfigure(0, weight=1)
        data_frame.grid_columnconfigure(0, weight=1)
        
        # تعريف الأعمدة
        tree["columns"] = list(df.columns)
        tree.column("#0", width=50, minwidth=50)
        tree.heading("#0", text="الرقم")
        
        for col in df.columns:
            tree.column(col, width=120, minwidth=50, anchor="w")
            tree.heading(col, text=str(col))
        
        # إضافة البيانات
        for i, row in df.head(100).iterrows():
            values = [str(row[col]) if pd.notna(row[col]) else "" for col in df.columns]
            tree.insert("", "end", text=str(i+1), values=values)
        
        # إطار الإدخال
        input_frame = tk.Frame(dialog)
        input_frame.pack(fill="x", padx=10, pady=10)
        
        tk.Label(input_frame, text="اختر الأعمدة (مثال: 1,2,3 أو A,B,C أو أسماء الأعمدة مفصولة بفاصلة):").pack(anchor="w")
        
        entry = tk.Entry(input_frame, width=80)
        entry.pack(fill="x", pady=5)
        
        # زر المساعدة
        def show_help():
            help_text = """
            كيفية اختيار الأعمدة:
            1. باستخدام الأرقام: 1,2,3 (لاختيار الأعمدة 1 و 2 و 3)
            2. باستخدام الحروف: A,B,C (لاختيار الأعمدة A و B و C)
            3. باستخدام أسماء الأعمدة: الاسم1,الاسم2
            
            ملاحظة: يمكنك تحديد نطاق مثل 1-3 أو A-C
            """
            messagebox.showinfo("مساعدة", help_text)
        
        help_btn = tk.Button(input_frame, text="مساعدة", command=show_help)
        help_btn.pack(side="left", padx=5)
        
        result = {'columns': None}
        
        def submit():
            result['columns'] = entry.get()
            dialog.destroy()
        
        submit_btn = tk.Button(input_frame, text="موافق", command=submit)
        submit_btn.pack(side="right", padx=5)
        
        dialog.transient(self.root)
        dialog.grab_set()
        self.root.wait_window(dialog)
        
        return result['columns']
    
    def parse_column_selection(self, selection, df):
        """تحويل اختيار الأعمدة إلى قائمة أسماء أعمدة"""
        columns = []
        for part in selection.split(','):
            part = part.strip()
            
            # معالجة النطاقات (مثل 1-3 أو A-C)
            if '-' in part:
                start, end = part.split('-')
                start = start.strip()
                end = end.strip()
                
                # تحويل النطاق إلى قائمة أعمدة
                try:
                    if start.isdigit() and end.isdigit():
                        # نطاق أرقام
                        start_idx = int(start) - 1
                        end_idx = int(end) - 1
                        columns.extend(df.columns[start_idx:end_idx+1])
                    elif start.isalpha() and end.isalpha():
                        # نطاق حروف
                        start_idx = ord(start.upper()) - ord('A')
                        end_idx = ord(end.upper()) - ord('A')
                        columns.extend(df.columns[start_idx:end_idx+1])
                except:
                    continue
            else:
                # معالجة العناصر المفردة
                if part.isdigit():
                    col_idx = int(part) - 1
                    if 0 <= col_idx < len(df.columns):
                        columns.append(df.columns[col_idx])
                elif part.isalpha():
                    col_idx = ord(part.upper()) - ord('A')
                    if 0 <= col_idx < len(df.columns):
                        columns.append(df.columns[col_idx])
                elif part in df.columns:
                    columns.append(part)
        
        return list(set(columns))  # إزالة التكرارات
    
    def merge_files(self):
        """وظيفة الدمج الرئيسية"""
        input_folder = self.folder_var.get()
        output_file = self.output_file_var.get()
        
        if not input_folder or not output_file:
            messagebox.showerror("خطأ", "يرجى تحديد مجلد الملفات واسم الملف الناتج")
            return
        
        excel_files = [f for f in os.listdir(input_folder) 
                      if f.lower().endswith(('.xls', '.xlsx', '.xlsm', '.ods'))]
        
        if not excel_files:
            messagebox.showerror("خطأ", "لا توجد ملفات Excel في المجلد المحدد")
            return
        
        merged_data = None
        common_columns = None
        
        for file in excel_files:
            file_path = os.path.join(input_folder, file)
            
            # اختيار الصفحة
            sheet_name = self.show_sheet_selection(file)
            if not sheet_name:
                continue
            
            # قراءة البيانات
            df = self.read_excel_safe(file_path, sheet_name)
            if df is None:
                continue
            
            # اختيار الأعمدة
            col_selection = self.show_column_selection(file, df)
            if not col_selection:
                continue
            
            selected_columns = self.parse_column_selection(col_selection, df)
            if not selected_columns:
                messagebox.showwarning("تحذير", f"لم يتم اختيار أعمدة صالحة في الملف {file}")
                continue
            
            df = df[selected_columns]
            
            # إضافة عمود المصدر
            df['📁 المصدر'] = f"{file} - {sheet_name}"
            
            # الدمج حسب الطريقة المختارة
            if self.merge_method_var.get() == "vertical":
                if merged_data is None:
                    merged_data = df
                    common_columns = df.columns.tolist()
                else:
                    # محاولة توحيد الأعمدة
                    try:
                        merged_data = pd.concat([merged_data, df], ignore_index=True)
                    except:
                        # إذا فشل الدمج بسبب اختلاف الأعمدة، نضيف لاحقة للأعمدة
                        suffix = f"_{len([c for c in merged_data.columns if '📁 المصدر' in str(c)])}"
                        df = df.add_suffix(suffix)
                        df = df.rename(columns={f'📁 المصدر{suffix}': '📁 المصدر'})
                        merged_data = pd.merge(merged_data, df, on='📁 المصدر', how='outer')
            else:  # الدمج الأفقي
                if merged_data is None:
                    merged_data = df
                else:
                    # إعادة تعيين الفهرس لضمان التوافق
                    df = df.reset_index(drop=True)
                    merged_data = merged_data.reset_index(drop=True)
                    
                    # الدمج الأفقي مع الحفاظ على الترتيب
                    merged_data = pd.concat([merged_data, df], axis=1)
        
        if merged_data is None or merged_data.empty:
            messagebox.showinfo("تنبيه", "لم يتم دمج أي بيانات")
            return
        
        # حفظ الملف الناتج
        try:
            if not output_file.lower().endswith('.xlsx'):
                output_file += '.xlsx'
            
            output_path = os.path.join(input_folder, output_file)
            
            # تحديد المحرك المناسب للحفظ
            engine = 'openpyxl' if output_file.endswith('.xlsx') else None
            
            with warnings.catch_warnings():
                warnings.simplefilter("ignore")
                merged_data.to_excel(output_path, index=False, engine=engine)
            
            messagebox.showinfo("نجاح", f"تم حفظ الملف المدمج بنجاح في:\n{os.path.abspath(output_path)}")
            
            # فتح الملف إذا أراد المستخدم
            if messagebox.askyesno("فتح الملف", "هل تريد فتح الملف المدمج الآن؟"):
                os.startfile(os.path.abspath(output_path))
        
        except Exception as e:
            messagebox.showerror("خطأ في الحفظ", f"حدث خطأ أثناء حفظ الملف:\n{str(e)}")

if __name__ == "__main__":
    root = tk.Tk()
    app = ExcelMerger(root)
    root.mainloop()