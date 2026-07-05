# Panduan Alur Login Aplikasi Mobile (Flutter)

Dokumen ini menjelaskan bagaimana aplikasi mobile (Flutter) harus menangani alur login, peran pengguna (roles), dan menampilkan pesan error dari backend setelah adanya pembatasan akses masuk bagi Super Admin.

---

## 1. Respons API Login Terbaru

### A. Login Berhasil (Untuk Staff/Cabang/Kasir)
Ketika akun dengan role selain `admin` (misal `cabang`) melakukan login, backend akan mengembalikan status `200 OK` dengan respons data seperti ini:
```json
{
  "success": true,
  "message": "Login berhasil.",
  "token": "1|abcdef123456...",
  "user": {
    "id": 2,
    "name": "mada",
    "email": "mada@email.com",
    "role": "cabang",
    "cabang_id": 2,
    "cabang": {
      "id": 2,
      "nama_cabang": "Cabang Mada",
      "alamat": "Alamat Cabang"
    }
  }
}
```

### B. Login Ditolak (Untuk Super Admin)
Ketika akun dengan role `admin` mencoba login melalui aplikasi mobile, backend akan mengembalikan status `403 Forbidden` dengan pesan error:
```json
{
  "success": false,
  "message": "Admin hanya dapat masuk melalui panel Website."
}
```

---

## 2. Contoh Implementasi di Flutter (Dart)

Berikut adalah contoh fungsi login menggunakan paket `http` di Flutter untuk menangkap dan menampilkan error ini dengan benar ke pengguna:

```dart
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

Future<void> loginUser(String email, String password, BuildContext context) async {
  final url = Uri.parse('https://your-ngrok-url.ngrok-free.app/api/login');

  try {
    final response = await http.post(
      url,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'ngrok-skip-browser-warning': 'true', // Mengabaikan peringatan ngrok
      },
      body: jsonEncode({
        'email': email,
        'password': password,
        'device_name': 'mobile_flutter',
      }),
    );

    final data = jsonDecode(response.body);

    if (response.statusCode == 200) {
      // Login Sukses
      String token = data['token'];
      String role = data['user']['role'];
      
      // Simpan token (misalnya menggunakan shared_preferences atau flutter_secure_storage)
      print("Login sukses! Role: $role");
      
      // Arahkan ke Halaman Utama Kasir
      Navigator.pushReplacementNamed(context, '/home');
      
    } else if (response.statusCode == 403) {
      // Ditampilkan jika Super Admin mencoba masuk
      showErrorDialog(context, data['message'] ?? 'Akses Ditolak');
      
    } else if (response.statusCode == 401) {
      // Ditampilkan jika Email/Password Salah
      showErrorDialog(context, data['message'] ?? 'Email atau password salah.');
      
    } else {
      showErrorDialog(context, 'Terjadi kesalahan sistem (${response.statusCode})');
    }
  } catch (e) {
    showErrorDialog(context, 'Gagal terhubung ke server. Pastikan server lokal dan ngrok aktif.');
  }
}

void showErrorDialog(BuildContext context, String message) {
  showDialog(
    context: context,
    builder: (ctx) => AlertDialog(
      title: const Text('Gagal Masuk'),
      content: Text(message),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(ctx).pop(),
          child: const Text('OK'),
        )
      ],
    ),
  );
}
```

---

## 3. Poin Penting untuk Developer Flutter
1. **Tampilkan Pesan Dinamis**: Pastikan aplikasi Flutter Anda menampilkan isi dari `"message"` yang dikirim langsung dari backend PHP agar pesan *"Admin hanya dapat masuk melalui panel Website"* tampil dengan seragam.
2. **Kunci Token**: Simpan `token` login dengan aman dan kirim sebagai header `Authorization: Bearer <token>` untuk request data menu, keranjang, dan transaksi setelahnya.
