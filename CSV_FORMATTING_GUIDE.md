# CSV File Formatting Guide

## Problem: Phone Numbers Converted to Scientific Notation

When Excel or other spreadsheet applications open CSV files, they often convert large numbers (like phone numbers) to scientific notation (e.g., `6.28122E+12` instead of `6281224377189`).

## Solutions

### Method 1: Use Text Editor (Recommended)
1. Open **Notepad** or any text editor
2. Create your CSV file with this format:
   ```
   name; phone
   Abdul A, M.d; 6281224377189
   Budi Santoso; 6281234567890
   ```
3. Save as `.csv` file
4. Upload directly to the system

### Method 2: Excel - Format as Text
1. Open Excel
2. Create your data:
   - Column A: Names
   - Column B: Phone numbers
3. **Select the phone number column**
4. Right-click → **Format Cells**
5. Choose **Text** category
6. Enter phone numbers (they will be treated as text)
7. Save as CSV

### Method 3: Excel - Use Apostrophe
1. In Excel, add an apostrophe before each phone number:
   ```
   name; phone
   Abdul A, M.d; '6281224377189
   Budi Santoso; '6281234567890
   ```
2. The apostrophe forces Excel to treat it as text
3. Save as CSV

### Method 4: Google Sheets
1. Open Google Sheets
2. Format phone column as **Plain text**
3. Enter phone numbers
4. Download as CSV

## Correct CSV Format

```
name; phone
Abdul A, M.d; 6281224377189
Budi Santoso; 6281234567890
Citra Dewi; 6282345678901
```

## Phone Number Rules

- **Must start with country code**: `62` for Indonesia
- **No spaces or special characters** (except the country code)
- **Examples**:
  - ✅ `6281224377189`
  - ✅ `6281234567890`
  - ❌ `081224377189` (missing country code)
  - ❌ `+6281224377189` (plus sign will be removed automatically)

## Testing Your CSV

1. Open your CSV file in a text editor (Notepad)
2. Verify phone numbers look like: `6281224377189`
3. If they look like `6.28122E+12`, use one of the methods above to fix

## Automatic Fix

The system now automatically detects and fixes scientific notation, but it's better to use properly formatted CSV files from the start.
