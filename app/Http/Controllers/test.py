import hashlib
import tkinter as tk
from tkinter import messagebox

def make_code(input_text: str, salt: str = "my_secret_salt") -> str:
    s = f"{salt}:{input_text}"
    h = hashlib.sha256(s.encode()).hexdigest()
    digits = []
    for i in range(5):
        chunk = h[i*12:(i+1)*12]
        n = int(chunk, 16)
        digits.append(str(n % 10))
    return "".join(digits)

# ---------------- UI ----------------
def generate_code():
    text = entry_number.get().strip()
    if not text:
        messagebox.showerror("Error", "Please enter a number or text!")
        return
    salt = entry_salt.get() if entry_salt.get() else "my_secret_salt"
    code = make_code(text, salt)
    lbl_result.config(text=f"✅ Code: {code}")

root = tk.Tk()
root.title("Code Generator")

tk.Label(root, text="Enter Number or Text:").pack(pady=5)
entry_number = tk.Entry(root)
entry_number.pack(pady=5)

tk.Label(root, text="Salt (optional):").pack(pady=5)
entry_salt = tk.Entry(root)
entry_salt.pack(pady=5)

btn_generate = tk.Button(root, text="Generate Code", command=generate_code)
btn_generate.pack(pady=10)

lbl_result = tk.Label(root, text="Code will appear here", font=("Arial", 12, "bold"))
lbl_result.pack(pady=10)

root.mainloop()
