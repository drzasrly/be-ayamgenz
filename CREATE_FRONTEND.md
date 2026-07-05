# Panduan Lengkap Pembuatan Frontend Aplikasi Kasir (Flutter Lintas Platform)

Dokumen ini berisi tutorial lengkap langkah-demi-langkah untuk membangun aplikasi frontend kasir lintas platform (**Android, iOS, dan Web/Desktop**) menggunakan **Flutter** yang terhubung ke Backend Laravel API yang telah kita siapkan.

---

## 1. Inisialisasi & Persiapan Proyek

### Langkah 1: Buat Proyek Flutter Baru
Jalankan perintah ini di terminal komputer Anda:
```bash
flutter create friedchicken_frontend
cd friedchicken_frontend
```

### Langkah 2: Tambahkan Dependensi
Buka file `pubspec.yaml` di proyek Flutter Anda, dan tambahkan dependensi berikut di bawah `dependencies:`:
```yaml
dependencies:
  flutter:
    sdk: flutter
  
  # HTTP Client untuk request ke API Laravel
  dio: ^5.4.3+1
  
  # Penyimpanan Token aman (Enkripsi)
  flutter_secure_storage: ^9.0.0
  
  # State Management untuk mengelola Keranjang & Autentikasi
  provider: ^6.1.2
  
  # WebView untuk membuka tautan pembayaran DOKU QRIS
  webview_flutter: ^4.7.0
  
  # Animasi Loading yang cantik
  flutter_spinkit: ^5.2.1
```
Setelah itu, jalankan:
```bash
flutter pub get
```

---

## 2. Struktur Folder Proyek yang Direkomendasikan
Buat struktur folder berikut di dalam folder `/lib` proyek Flutter Anda:
```text
lib/
├── models/          # Model data (User, Menu, Cart, Transaksi)
├── providers/       # State management (AuthProvider, CartProvider)
├── services/        # Komunikasi HTTP API (ApiService)
├── views/           # UI Screens (Login, Menu, Cart, WebView)
└── main.dart        # Entry point aplikasi
```

---

## 3. Implementasi Kode Utama

### A. File: `lib/models/menu.dart`
Model untuk memetakan data menu dari JSON API Laravel.
```dart
class MenuModel {
  final int id;
  final String namaMenu;
  final int harga;
  final String kategori;
  final int stok;
  final int cabangId;
  final String? gambar;

  MenuModel({
    required this.id,
    required this.namaMenu,
    required this.harga,
    required this.kategori,
    required this.stok,
    required this.cabangId,
    this.gambar,
  });

  factory MenuModel.fromJson(Map<String, dynamic> json) {
    return MenuModel(
      id: json['id'],
      namaMenu: json['nama_menu'],
      harga: json['harga'],
      kategori: json['kategori'],
      stok: json['stok'],
      cabangId: json['cabang_id'],
      gambar: json['gambar'],
    );
  }
}
```

---

### B. File: `lib/services/api_service.dart`
Mengurus semua request HTTP ke Backend Laravel menggunakan library `dio`.
```dart
import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

class ApiService {
  // Ganti IP dengan URL server Anda. 
  // Catatan: Jika menggunakan Emulator Android, localhost diakses via http://10.0.2.2:8000
  static const String baseUrl = 'http://10.0.2.2:8000/api';
  
  final Dio _dio = Dio(BaseOptions(
    baseUrl: baseUrl,
    connectTimeout: const Duration(seconds: 10),
    receiveTimeout: const Duration(seconds: 10),
  ));
  
  final _storage = const FlutterSecureStorage();

  // Helper untuk mendapatkan header dengan Bearer Token
  Future<Options> _getHeaders() async {
    final token = await _storage.read(key: 'auth_token');
    return Options(
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    );
  }

  // 1. LOGIN API
  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await _dio.post('/login', data: {
        'email': email,
        'password': password,
        'device_name': 'flutter_cashier',
      });
      
      if (response.data['success'] == true) {
        // Simpan token ke storage
        await _storage.write(key: 'auth_token', value: response.data['token']);
      }
      return response.data;
    } on DioException catch (e) {
      return e.response?.data ?? {'success': false, 'message': 'Gagal terhubung ke server'};
    }
  }

  // 2. LOGOUT API
  Future<bool> logout() async {
    try {
      final options = await _getHeaders();
      await _dio.post('/logout', options: options);
      await _storage.delete(key: 'auth_token');
      return true;
    } catch (_) {
      await _storage.delete(key: 'auth_token');
      return true;
    }
  }

  // 3. FETCH MENU API
  Future<List<dynamic>?> getMenus() async {
    try {
      final options = await _getHeaders();
      final response = await _dio.get('/menu', options: options);
      if (response.data['success'] == true) {
        return response.data['data'];
      }
    } catch (e) {
      print('Error fetching menu: $e');
    }
    return null;
  }

  // 4. CHECKOUT API (CASH / QRIS)
  Future<Map<String, dynamic>> checkout(String metodePembayaran, List<Map<String, dynamic>> items) async {
    try {
      final options = await _getHeaders();
      final response = await _dio.post('/checkout', 
        options: options,
        data: {
          'metode_pembayaran': metodePembayaran,
          'items': items
        }
      );
      return response.data;
    } on DioException catch (e) {
      return e.response?.data ?? {'success': false, 'message': 'Koneksi gagal saat checkout'};
    }
  }
}
```

---

### C. File: `lib/providers/cart_provider.dart`
State Management untuk menyimpan keranjang belanjaan lokal kasir sebelum checkout.
```dart
import 'package:flutter/material.dart';
import '../models/menu.dart';

class CartItem {
  final MenuModel menu;
  int quantity;

  CartItem({required this.menu, required this.quantity});
}

class CartProvider with ChangeNotifier {
  final Map<int, CartItem> _items = {};

  Map<int, CartItem> get items => _items;

  int get totalAmount {
    var total = 0;
    _items.forEach((key, cartItem) {
      total += cartItem.menu.harga * cartItem.quantity;
    });
    return total;
  }

  int get itemCount => _items.length;

  void addToCart(MenuModel menu) {
    if (_items.containsKey(menu.id)) {
      _items[menu.id]!.quantity += 1;
    } else {
      _items.putIfAbsent(menu.id, () => CartItem(menu: menu, quantity: 1));
    }
    notifyListeners();
  }

  void decreaseQuantity(int menuId) {
    if (!_items.containsKey(menuId)) return;
    if (_items[menuId]!.quantity > 1) {
      _items[menuId]!.quantity -= 1;
    } else {
      _items.remove(menuId);
    }
    notifyListeners();
  }

  void removeItem(int menuId) {
    _items.remove(menuId);
    notifyListeners();
  }

  void clearCart() {
    _items.clear();
    notifyListeners();
  }

  List<Map<String, dynamic>> getFormattedItems() {
    return _items.values.map((item) => {
      'menu_id': item.menu.id,
      'qty': item.quantity
    }).toList();
  }
}
```

---

## 4. Tampilan Antarmuka (UI Screens)

### A. Tampilan Login (`lib/views/login_screen.dart`)
Tampilan login dengan desain modern minimalis.
```dart
import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'menu_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _isLoading = false;

  void _handleLogin() async {
    setState(() => _isLoading = true);
    final res = await ApiService().login(_emailController.text, _passwordController.text);
    setState(() => _isLoading = false);

    if (res['success'] == true) {
      Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const MenuScreen()));
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Login Gagal'), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xff121212),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Text("🍗 FRIED CHICKEN KASIR", style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Colors.amber)),
              const SizedBox(height: 32),
              TextField(
                controller: _emailController,
                style: const TextStyle(color: Colors.white),
                decoration: InputDecoration(
                  labelText: 'Email', labelStyle: const TextStyle(color: Colors.amber),
                  enabledBorder: OutlineInputBorder(borderSide: const BorderSide(color: Colors.amber), borderRadius: BorderRadius.circular(12)),
                  focusedBorder: OutlineInputBorder(borderSide: const BorderSide(color: Colors.white), borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: _passwordController,
                obscureText: true,
                style: const TextStyle(color: Colors.white),
                decoration: InputDecoration(
                  labelText: 'Password', labelStyle: const TextStyle(color: Colors.amber),
                  enabledBorder: OutlineInputBorder(borderSide: const BorderSide(color: Colors.amber), borderRadius: BorderRadius.circular(12)),
                  focusedBorder: OutlineInputBorder(borderSide: const BorderSide(color: Colors.white), borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 24),
              _isLoading 
                ? const CircularProgressIndicator(color: Colors.amber)
                : SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(backgroundColor: Colors.amber, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12))),
                      onPressed: _handleLogin,
                      child: const Text('MASUK', style: TextStyle(color: Colors.black, fontWeight: FontWeight.bold)),
                    ),
                  ),
            ],
          ),
        ),
      ),
    );
  }
}
```

---

### B. Tampilan WebView Pembayaran QRIS (`lib/views/payment_webview.dart`)
Tampilan untuk membuka halaman pembayaran DOKU QRIS dan mendeteksi secara otomatis saat pembayaran berhasil (redireksi kembali).
```dart
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

class PaymentWebView extends StatefulWidget {
  final String url;
  const PaymentWebView({super.key, required this.url});

  @override
  State<PaymentWebView> createState() => _PaymentWebViewState();
}

class _PaymentWebViewState extends State<PaymentWebView> {
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onNavigationRequest: (NavigationRequest request) {
            // Deteksi jika DOKU meredireksi kembali ke callback URL
            if (request.url.contains('/kasir')) {
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(content: Text('Pembayaran Sukses / QRIS Diproses!'), backgroundColor: Colors.green),
              );
              Navigator.pop(context, true); // Tutup WebView dengan status sukses
              return NavigationDecision.prevent;
            }
            return NavigationDecision.navigate;
          },
        ),
      )
      ..loadRequest(Uri.parse(widget.url));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pembayaran QRIS'),
        backgroundColor: Colors.amber,
        foregroundColor: Colors.black,
      ),
      body: WebViewWidget(controller: _controller),
    );
  }
}
```

---

### C. Tampilan Utama Pemesanan Kasir (`lib/views/menu_screen.dart`)
Tampilan kasir untuk memilih menu makanan, menambah ke keranjang, dan melakukan checkout.
```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../services/api_service.dart';
import '../providers/cart_provider.dart';
import '../models/menu.dart';
import 'payment_webview.dart';
import 'login_screen.dart';

class MenuScreen extends StatefulWidget {
  const MenuScreen({super.key});

  @override
  State<MenuScreen> createState() => _MenuScreenState();
}

class _MenuScreenState extends State<MenuScreen> {
  List<MenuModel> _menus = [];
  bool _isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadMenus();
  }

  Future<void> _loadMenus() async {
    final rawData = await ApiService().getMenus();
    if (rawData != null) {
      setState(() {
        _menus = rawData.map((e) => MenuModel.fromJson(e)).toList();
        _isLoading = false;
      });
    } else {
      setState(() => _isLoading = false);
    }
  }

  void _handleCheckout(String metode) async {
    final cart = Provider.of<CartProvider>(context, listen: false);
    if (cart.itemCount == 0) return;

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator(color: Colors.amber)),
    );

    final res = await ApiService().checkout(metode, cart.getFormattedItems());
    Navigator.pop(context); // Tutup loading dialog

    if (res['success'] == true) {
      if (metode == 'qris') {
        String? paymentUrl = res['data']['payment_url'];
        if (paymentUrl != null) {
          // Buka halaman pembayaran DOKU
          final paid = await Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => PaymentWebView(url: paymentUrl)),
          );
          if (paid == true) {
            cart.clearCart();
          }
        }
      } else {
        // Pembayaran cash sukses langsung
        cart.clearCart();
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Transaksi Cash Berhasil!'), backgroundColor: Colors.green),
        );
      }
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(res['message'] ?? 'Checkout gagal'), backgroundColor: Colors.red),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final cart = Provider.of<CartProvider>(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Menu Kasir Fried Chicken', style: TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: Colors.amber,
        foregroundColor: Colors.black,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () async {
              await ApiService().logout();
              Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
            },
          )
        ],
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: Colors.amber))
          : Column(
              children: [
                Expanded(
                  child: GridView.builder(
                    padding: const EdgeInsets.all(12),
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 2, childAspectRatio: 0.8, crossAxisSpacing: 12, mainAxisSpacing: 12
                    ),
                    itemCount: _menus.length,
                    itemBuilder: (ctx, idx) {
                      final item = _menus[idx];
                      return Card(
                        clipBehavior: Clip.antiAlias,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Expanded(
                              child: item.gambar != null
                                  ? Image.network(item.gambar!, width: double.infinity, fit: Cover)
                                  : Container(color: Colors.grey[300], child: const Icon(Icons.fastfood, size: 50)),
                            ),
                            Padding(
                              padding: const EdgeInsets.all(8),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(item.namaMenu, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                  Text('Rp ${item.harga}', style: const TextStyle(color: Colors.green, fontWeight: FontWeight.w600)),
                                  Text('Stok: ${item.stok}', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                                  const SizedBox(height: 8),
                                  SizedBox(
                                    width: double.infinity,
                                    child: ElevatedButton(
                                      style: ElevatedButton.styleFrom(backgroundColor: Colors.amber),
                                      onPressed: () => cart.addToCart(item),
                                      child: const Text('Tambah', style: TextStyle(color: Colors.black)),
                                    ),
                                  )
                                ],
                              ),
                            )
                          ],
                        ),
                      );
                    },
                  ),
                ),
                // Panel Ringkasan Keranjang Belanja di bagian bawah
                if (cart.itemCount > 0)
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(color: Colors.white, boxShadow: [BoxShadow(color: Colors.grey.withOpacity(0.5), blurRadius: 10)]),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('${cart.itemCount} Item terpilih', style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                            Text('Total: Rp ${cart.totalAmount}', style: const TextStyle(fontSize: 18, color: Colors.green, fontWeight: FontWeight.bold)),
                          ],
                        ),
                        const SizedBox(height: 12),
                        Row(
                          children: [
                            Expanded(
                              child: ElevatedButton(
                                style: ElevatedButton.styleFrom(backgroundColor: Colors.green, padding: const EdgeInsets.symmetric(vertical: 14)),
                                onPressed: () => _handleCheckout('cash'),
                                child: const Text('BAYAR CASH', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: ElevatedButton(
                                style: ElevatedButton.styleFrom(backgroundColor: Colors.blue, padding: const EdgeInsets.symmetric(vertical: 14)),
                                onPressed: () => _handleCheckout('qris'),
                                child: const Text('BAYAR QRIS', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                              ),
                            ),
                          ],
                        )
                      ],
                    ),
                  )
              ],
            ),
    );
  }
}
```

---

### D. File: `lib/main.dart`
Titik masuk utama aplikasi untuk memuat Provider dan halaman awal.
```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'providers/cart_provider.dart';
import 'views/login_screen.dart';

void main() {
  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => CartProvider()),
      ],
      child: const MyApp(),
    ),
  );
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Kasir Fried Chicken',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        brightness: Brightness.dark,
        primarySwatch: Colors.amber,
        scaffoldBackgroundColor: const Color(0xff121212),
      ),
      home: const LoginScreen(),
    );
  }
}
```

---

## 5. Menjalankan Aplikasi
1. Nyalakan server backend Laravel Anda (`php artisan serve` atau via panel Laragon).
2. Hubungkan HP Android/iOS Anda menggunakan kabel data (aktifkan USB Debugging) ATAU nyalakan emulator.
3. Jalankan aplikasi Flutter:
   ```bash
   flutter run
   ```
   Pilih emulator atau device fisik yang terhubung untuk mulai mencoba aplikasi kasir.
