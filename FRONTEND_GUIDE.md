# Panduan Integrasi Frontend Aplikasi Cross-Platform (API Backend)

Dokumen ini menjelaskan cara menghubungkan aplikasi frontend Anda (seperti **Flutter**, **React Native**, atau **React/Vue Web**) dengan API Backend Laravel yang sudah kita siapkan.

---

## 1. Keamanan & Autentikasi (Token-Based)

Semua endpoint dilindungi oleh **Laravel Sanctum** (kecuali `/api/login`). 
Untuk mengakses endpoint yang dilindungi, aplikasi Anda harus menyertakan token autentikasi di dalam header HTTP:

```http
Authorization: Bearer <token_anda_di_sini>
Accept: application/json
Content-Type: application/json
```

> [!IMPORTANT]
> Di sisi aplikasi mobile (Flutter / React Native), simpan token secara aman setelah login menggunakan library seperti:
> - **Flutter**: `flutter_secure_storage`
> - **React Native**: `react-native-keychain` atau `expo-secure-store`

---

## 2. Dokumentasi Endpoint API

Secara default, URL dasar (Base URL) backend Anda di lokal adalah:
`http://localhost:8000/api` atau domain lokal Laragon Anda (misal `http://friedchicken.test/api`).

### A. Login User
* **Endpoint:** `POST /api/login`
* **Request Body (JSON):**
  ```json
  {
    "email": "kasir@gmail.com",
    "password": "password123",
    "device_name": "android_phone"
  }
  ```
* **Response Sukses (200 OK):**
  ```json
  {
    "success": true,
    "message": "Login berhasil.",
    "token": "1|uX4YQyEpHfD9s...",
    "user": {
      "id": 2,
      "name": "Kasir Cabang A",
      "email": "kasir@gmail.com",
      "role": "kasir",
      "cabang_id": 1,
      "cabang": {
        "id": 1,
        "nama_cabang": "Cabang Utama",
        "alamat": "Jl. Merdeka No. 1"
      }
    }
  }
  ```

---

### B. Ambil Profil User Terlogin
* **Endpoint:** `GET /api/user`
* **Header:** `Authorization: Bearer <token>`
* **Response Sukses (200 OK):**
  ```json
  {
    "success": true,
    "user": {
      "id": 2,
      "name": "Kasir Cabang A",
      "email": "kasir@gmail.com",
      "role": "kasir",
      "cabang_id": 1,
      "cabang": {
        "id": 1,
        "nama_cabang": "Cabang Utama",
        "alamat": "Jl. Merdeka No. 1"
      }
    }
  }
  ```

---

### C. Ambil Daftar Menu Makanan
Menu yang dikembalikan otomatis terfilter sesuai dengan cabang user yang sedang login (kecuali user adalah admin, yang akan melihat semua menu atau dapat memfilter lewat parameter `?cabang_id=1`).
* **Endpoint:** `GET /api/menu`
* **Header:** `Authorization: Bearer <token>`
* **Response Sukses (200 OK):**
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "nama_menu": "Ayam Goreng Dada",
        "harga": 15000,
        "kategori": "makanan",
        "stok": 50,
        "cabang_id": 1,
        "cabang_name": "Cabang Utama",
        "gambar": "http://localhost:8000/storage/menus/dada.jpg",
        "created_at": "2026-06-26T12:00:00.000000Z",
        "updated_at": "2026-06-26T12:00:00.000000Z"
      }
    ]
  }
  ```

---

### D. Ambil Daftar Cabang
* **Endpoint:** `GET /api/cabang`
* **Header:** `Authorization: Bearer <token>`
* **Response Sukses (200 OK):**
  ```json
  {
    "success": true,
    "data": [
      {
        "id": 1,
        "nama_cabang": "Cabang Utama",
        "alamat": "Jl. Merdeka No. 1",
        "telepon": "08123456789",
        "created_at": "2026-06-26T12:00:00.000000Z",
        "updated_at": "2026-06-26T12:00:00.000000Z"
      }
    ]
  }
  ```

---

### E. Checkout Transaksi (Pembelian)
Mendukung pembelian multiple items sekaligus.
* **Endpoint:** `POST /api/checkout`
* **Header:** `Authorization: Bearer <token>`
* **Pilihan 1: Pembayaran CASH (Tunai)**
  * **Request Body (JSON):**
    ```json
    {
      "metode_pembayaran": "cash",
      "items": [
        {
          "menu_id": 1,
          "qty": 2
        },
        {
          "menu_id": 3,
          "qty": 1
        }
      ]
    }
    ```
  * **Response Sukses (201 Created):**
    Stok menu otomatis berkurang di backend.
    ```json
    {
      "success": true,
      "message": "Transaksi Cash berhasil disimpan.",
      "data": {
        "invoice": "INV-1719401234",
        "total": 45000,
        "status": "success",
        "metode_pembayaran": "cash"
      }
    }
    ```

* **Pilihan 2: Pembayaran QRIS (Melalui DOKU)**
  * **Request Body (JSON):**
    ```json
    {
      "metode_pembayaran": "qris",
      "items": [
        {
          "menu_id": 1,
          "qty": 2
        }
      ]
    }
    ```
  * **Response Sukses (201 Created):**
    Status transaksi akan berstatus `pending` sampai pembeli membayar lunas. Backend mengembalikan `payment_url` dari DOKU.
    ```json
    {
      "success": true,
      "message": "Transaksi QRIS berhasil dibuat. Silakan selesaikan pembayaran.",
      "data": {
        "invoice": "INV-1719401234",
        "total": 30000,
        "status": "pending",
        "metode_pembayaran": "qris",
        "payment_url": "https://sandbox.doku.com/checkout/v1/payment/link/XYZ123..."
      }
    }
    ```

---

## 3. Contoh Kode Implementasi Frontend

### A. Contoh di Flutter (Dart menggunakan `dio`)

```dart
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiService {
  final Dio _dio = Dio(BaseOptions(baseUrl: 'http://10.0.2.2:8000/api')); // Gunakan 10.0.2.2 untuk Emulator Android ke localhost
  final _storage = const FlutterSecureStorage();

  // 1. LOGIN
  Future<String?> login(String email, String password) async {
    try {
      final response = await _dio.post('/login', data: {
        'email': email,
        'password': password,
        'device_name': 'flutter_app',
      });
      if (response.data['success']) {
        String token = response.data['token'];
        await _storage.write(key: 'auth_token', value: token);
        return token;
      }
    } catch (e) {
      print('Login error: $e');
    }
    return null;
  }

  // 2. FETCH MENU (DENGAN BEARER TOKEN)
  Future<List<dynamic>> fetchMenu() async {
    try {
      String? token = await _storage.read(key: 'auth_token');
      final response = await _dio.get('/menu',
        options: Options(headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        }),
      );
      return response.data['data'];
    } catch (e) {
      print('Fetch menu error: $e');
      return [];
    }
  }

  // 3. CHECKOUT
  Future<Map<String, dynamic>?> checkout(String metode, List<Map<String, dynamic>> items) async {
    try {
      String? token = await _storage.read(key: 'auth_token');
      final response = await _dio.post('/checkout',
        data: {
          'metode_pembayaran': metode,
          'items': items,
        },
        options: Options(headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        }),
      );
      return response.data;
    } catch (e) {
      print('Checkout error: $e');
      return null;
    }
  }
}
```

---

### B. Contoh di React Native (JavaScript menggunakan `axios` + Interceptors)

```javascript
import axios from 'axios';
import * as SecureStore from 'expo-secure-store';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
});

// Interceptor untuk menyisipkan Token otomatis di setiap request
api.interceptors.request.use(
  async (config) => {
    const token = await SecureStore.getItemAsync('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    config.headers.Accept = 'application/json';
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Panggilan Checkout QRIS
export const processCheckoutQris = async (cartItems) => {
  try {
    const payload = {
      metode_pembayaran: 'qris',
      items: cartItems.map(item => ({
        menu_id: item.id,
        qty: item.quantity
      }))
    };

    const response = await api.post('/checkout', payload);
    
    if (response.data.success) {
      const paymentUrl = response.data.data.payment_url;
      // Gunakan WebView di React Native (misal 'react-native-webview') untuk membuka paymentUrl ini
      return paymentUrl;
    }
  } catch (error) {
    console.error("Checkout QRIS failed", error);
  }
};
```

---

## 4. Cara Menangani Pembayaran QRIS DOKU di Mobile App
Ketika response checkout `qris` menghasilkan `payment_url`:
1. Buka tautan tersebut di dalam aplikasi menggunakan **WebView** (`webview_flutter` di Flutter atau `react-native-webview` di React Native).
2. Pantau URL WebView menggunakan pendengar perubahan navigasi (navigation state changes listener).
3. Setelah pembayaran berhasil, DOKU akan meredirek halaman WebView ke `/kasir` (sesuai setting `callback_url`).
4. Ketika WebView mendeteksi navigasi mengarah ke URL callback `/kasir` tersebut:
   - Tampilkan pesan sukses di aplikasi.
   - Tutup halaman WebView secara otomatis dan kembali ke halaman utama kasir.
