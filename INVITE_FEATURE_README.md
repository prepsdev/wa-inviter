# WhatsApp Group Invite Feature

## Overview
This feature allows you to automatically send WhatsApp messages to participants with group invite links using CSV data.

## How to Use

### 1. Prepare Your CSV File
Create a CSV file with the following format:
```csv
name; phone
Abdul A, M.d; 6281224377189
Budi Santoso; 6281234567890
Citra Dewi; 6282345678901
```

**Important Notes:**
- Use semicolon (;) as separator
- Include header row: `name; phone`
- Phone numbers should be in international format (e.g., 6281234567890)
- The system will automatically format Indonesian numbers if they start with 0

### 2. Using the Invite Feature

1. **Start the Server**
   ```bash
   npm start
   ```

2. **Open the Web Interface**
   - Navigate to `http://localhost/wa-inviter` (or your XAMPP URL)
   - Make sure WhatsApp is connected (scan QR code if needed)

3. **Select a Group**
   - Find the group you want to invite people to
   - Click the "Invite CSV" button on the group card

4. **Fill in the Form**
   - **Nama Diklat**: Enter the name of your training/event
   - **Kelompok**: Enter the group/class name
   - **Upload CSV**: Select your CSV file with participant data
   - **Message Template**: Customize the message (optional)

5. **Send Invites**
   - Click "Send Invites" to start sending messages
   - The system will show progress and results

### 3. Message Template

The default message template is:
```
Halo, {name}.

Kamu terdaftar sebagai peserta Diklat {nama_diklat} dengan group kelompok {kelompok}, silahkan klik link dibawah untuk bergabung kedalam group Whatsapp peserta Diklat {nama_diklat}.

{link}
```

**Available Placeholders:**
- `{name}` - Participant's name from CSV
- `{nama_diklat}` - Training name you entered
- `{kelompok}` - Group name you entered
- `{link}` - WhatsApp group invite link

### 4. Features

- **CSV Preview**: See your data before sending
- **Automatic Phone Formatting**: Handles Indonesian phone numbers
- **Rate Limiting**: 1-second delay between messages to avoid spam
- **Progress Tracking**: Shows success/failure counts
- **Error Handling**: Continues sending even if some messages fail

### 5. Sample CSV File

A sample file `sample_participants.csv` is included for testing.

### 6. Troubleshooting

**"Failed to connect to API Server"**
- Make sure the Node.js server is running (`npm start`)
- Check if port 3000 is available

**"WhatsApp not connected"**
- Scan the QR code to connect WhatsApp
- Make sure your phone has internet connection

**"Failed to send message"**
- Check if the phone number is correct
- Ensure the recipient has WhatsApp installed
- Some numbers may be blocked or invalid

## API Endpoints

- `POST /api/invite-link` - Generate group invite link
- `POST /api/send-message` - Send message to phone number

## Security Notes

- Phone numbers are automatically formatted for Indonesian numbers
- Messages are sent with a 1-second delay to avoid rate limiting
- All API calls require WhatsApp to be connected
