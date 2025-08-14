import sys
from PyPDF2 import PdfReader

def extract_text_from_pdf(file_path):
    reader = PdfReader(file_path)
    text = ''
    for page in reader.pages:
        text += page.extract_text() or ''
    return text

if __name__ == "__main__":
    file_path = sys.argv[1]
    try:
        extracted_text = extract_text_from_pdf(file_path)
        print(extracted_text)
    except Exception as e:
        print("ERROR:", e)
