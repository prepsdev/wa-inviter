# WhatsApp Group Manager - Baileys Integration

Aplikasi untuk mengelola grup WhatsApp menggunakan Baileys library dengan interface web PHP dan Bootstrap 5.

## Fitur

- ✅ Koneksi WhatsApp menggunakan QR Code
- ✅ Real-time status update menggunakan Socket.IO
- ✅ Menampilkan daftar grup WhatsApp
- ✅ Interface web responsif dengan Bootstrap 5
- ✅ Copy Group ID ke clipboard
- ✅ Auto-reconnect ketika terputus

## Teknologi yang Digunakan

### Backend (Node.js)
- **Baileys**: Library WhatsApp Web API
- **Express.js**: Web framework
- **Socket.IO**: Real-time communication
- **QRCode**: Generate QR code untuk login
- **CORS**: Cross-origin resource sharing

### Frontend (PHP + Bootstrap)
- **Bootstrap 5**: CSS framework
- **Socket.IO Client**: Real-time updates
- **Bootstrap Icons**: Icon pack

## Instalasi

### 1. Install Dependencies Node.js

```bash
npm install
```

### 2. Jalankan Server Node.js

```bash
# Development mode
npm run dev

# Production mode
npm start
```

Server akan berjalan di `http://localhost:3000`

### 3. Setup Web Server untuk PHP

Pastikan Anda memiliki web server yang support PHP (Apache/Nginx/XAMPP/WAMP).

Akses `index.php` melalui web browser Anda.

## Cara Penggunaan

### 1. Koneksi WhatsApp

1. Buka `index.php` di browser
2. Klik tombol "Connect"
3. Scan QR Code yang muncul dengan aplikasi WhatsApp:
   - Buka WhatsApp
   - Tap titik tiga (⋮) di pojok kanan atas
   - Pilih "Linked Devices"
   - Tap "Link a Device"
   - Scan QR Code

### 2. Melihat Daftar Grup

Setelah berhasil login, daftar grup WhatsApp akan otomatis muncul dengan informasi:
- Nama grup
- Jumlah anggota
- Group ID
- Deskripsi grup (jika ada)

### 3. Copy Group ID

Klik tombol "Copy ID" pada kartu grup untuk menyalin Group ID ke clipboard.

## API Endpoints

### GET `/api/status`
Mendapatkan status koneksi saat ini
```json
{
  "status": "connected",
  "qrCode": null,
  "groups": [...]
}
```

### GET `/api/qr`
Mendapatkan QR Code untuk login
```json
{
  "success": true,
  "qrCode": "data:image/png;base64,..."
}
```

### GET `/api/groups`
Mendapatkan daftar grup WhatsApp
```json
{
  "success": true,
  "groups": [
    {
      "id": "120363043968482639@g.us",
      "name": "Grup Example",
      "participants": 25,
      "description": "Deskripsi grup"
    }
  ]
}
```

### POST `/api/connect`
Memulai koneksi ke WhatsApp
```json
{
  "success": true,
  "message": "Connection initiated"
}
```

### POST `/api/disconnect`
Memutus koneksi WhatsApp
```json
{
  "success": true,
  "message": "Disconnected successfully"
}
```

## Socket.IO Events

### Client → Server
- `connect`: Koneksi socket established
- `disconnect`: Socket disconnected

### Server → Client
- `status`: Status koneksi dan data initial
- `qr_code`: QR Code baru tersedia
- `status_update`: Update status koneksi

## Status Koneksi

- **disconnected**: Tidak terhubung
- **connecting**: Sedang menghubungkan
- **qr_ready**: QR Code siap untuk di-scan
- **connected**: Terhubung dan siap digunakan
- **logged_out**: Keluar dari WhatsApp
- **error**: Terjadi error
- **reconnecting**: Sedang mencoba reconnect

## File Structure

```
wa-inviter/
├── server.js          # Node.js API server
├── package.json       # Dependencies dan scripts
├── index.php         # Interface web PHP
├── README.md         # Dokumentasi
└── auth_info_baileys/ # Folder autentikasi (otomatis dibuat)
```

## Troubleshooting

### QR Code tidak muncul
- Pastikan server Node.js berjalan
- Check console browser untuk error
- Pastikan tidak ada firewall yang memblokir port 3000

### Koneksi terputus terus
- Pastikan koneksi internet stabil
- Check apakah WhatsApp Web sedang aktif di device lain
- Restart server jika diperlukan

### Error saat mengambil grup
- Pastikan sudah login dan status "connected"
- Refresh halaman dan coba lagi

## Development

Untuk development, gunakan:

```bash
npm run dev
```

Ini akan menjalankan server dengan auto-reload menggunakan nodemon.

## Security Notes

- Jangan share file `auth_info_baileys/` karena berisi session data
- Gunakan HTTPS di production
- Implementasikan rate limiting untuk API endpoints
- Validasi input dari client

## Next Steps

Aplikasi ini adalah fondasi untuk sistem yang lebih kompleks. Beberapa fitur yang bisa ditambahkan:

- Kirim pesan ke grup
- Invite member ke grup
- Kelola admin grup
- Export data grup
- Monitoring activity grup
- Integration dengan database
