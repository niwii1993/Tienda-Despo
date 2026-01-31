import pypdf
import sys

pdf_file = "Manual ApiSigma Desarrollador.pdf"
output_file = "api_pdf_extracted.txt"

try:
    reader = pypdf.PdfReader(pdf_file)
    with open(output_file, "w", encoding="utf-8") as f:
        for page in reader.pages:
            f.write(page.extract_text() + "\n\n")
    print(f"Successfully extracted text to {output_file}")
except Exception as e:
    print(f"Error extracting PDF: {e}")
