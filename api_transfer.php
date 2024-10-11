<?php
// File: transfer_api.php (gunakan file ini di kedua sistem)

// Konfigurasi database (sesuaikan dengan konfigurasi masing-masing sistem)
$db_config = [
    'host' => 'localhost',
    'user' => 'username_db',
    'pass' => 'password_db',
    'name' => 'nama_database'
];

$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass'], $db_config['name']);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Fungsi untuk memproses transfer masuk
function terimaTransfer($data) {
    global $conn;
    
    $akun_tujuan = $data['akun_tujuan'];
    $jumlah = $data['jumlah'];
    $id_transaksi = $data['id_transaksi'];
    
    // Validasi akun tujuan
    $stmt = $conn->prepare("SELECT id FROM akun WHERE nomor_akun = ?");
    $stmt->bind_param("s", $akun_tujuan);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['status' => 'gagal', 'pesan' => 'Akun tujuan tidak ditemukan'];
    }
    
    // Proses penambahan saldo
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE akun SET saldo = saldo + ? WHERE nomor_akun = ?");
        $stmt->bind_param("ds", $jumlah, $akun_tujuan);
        $stmt->execute();
        
        $stmt = $conn->prepare("INSERT INTO transaksi (id_transaksi, akun_tujuan, jumlah, tipe) VALUES (?, ?, ?, 'masuk')");
        $stmt->bind_param("ssd", $id_transaksi, $akun_tujuan, $jumlah);
        $stmt->execute();
        
        $conn->commit();
        return ['status' => 'sukses', 'pesan' => 'Transfer berhasil diterima'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['status' => 'gagal', 'pesan' => 'Gagal memproses transfer: ' . $e->getMessage()];
    }
}

// Fungsi untuk mengirim transfer
function kirimTransfer($akun_asal, $akun_tujuan, $jumlah) {
    global $conn;
    
    // Validasi saldo akun asal
    $stmt = $conn->prepare("SELECT saldo FROM akun WHERE nomor_akun = ? FOR UPDATE");
    $stmt->bind_param("s", $akun_asal);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['status' => 'gagal', 'pesan' => 'Akun asal tidak ditemukan'];
    }
    
    $saldo = $result->fetch_assoc()['saldo'];
    if ($saldo < $jumlah) {
        return ['status' => 'gagal', 'pesan' => 'Saldo tidak mencukupi'];
    }
    
    // Proses pengurangan saldo dan kirim ke sistem lain
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE akun SET saldo = saldo - ? WHERE nomor_akun = ?");
        $stmt->bind_param("ds", $jumlah, $akun_asal);
        $stmt->execute();
        
        $id_transaksi = uniqid();
        $stmt = $conn->prepare("INSERT INTO transaksi (id_transaksi, akun_asal, akun_tujuan, jumlah, tipe) VALUES (?, ?, ?, ?, 'keluar')");
        $stmt->bind_param("sssd", $id_transaksi, $akun_asal, $akun_tujuan, $jumlah);
        $stmt->execute();
        
        // Kirim ke sistem lain
        $data = [
            'akun_tujuan' => $akun_tujuan,
            'jumlah' => $jumlah,
            'id_transaksi' => $id_transaksi
        ];
        
        $url = 'http://sistem-lain.com/transfer_api.php'; // Ganti dengan URL sistem lain
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data)
            ]
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            throw new Exception("Gagal mengirim ke sistem lain");
        }
        
        $response = json_decode($result, true);
        if ($response['status'] !== 'sukses') {
            throw new Exception($response['pesan']);
        }
        
        $conn->commit();
        return ['status' => 'sukses', 'pesan' => 'Transfer berhasil dikirim'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['status' => 'gagal', 'pesan' => 'Gagal memproses transfer: ' . $e->getMessage()];
    }
}

// Handle request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (isset($data['akun_tujuan']) && isset($data['jumlah']) && isset($data['id_transaksi'])) {
        $hasil = terimaTransfer($data);
    } else {
        $hasil = ['status' => 'gagal', 'pesan' => 'Data tidak lengkap'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($hasil);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Contoh penggunaan untuk mengirim transfer (biasanya dipanggil dari antarmuka pengguna)
    if (isset($_GET['kirim'])) {
        $hasil = kirimTransfer('1234567890', '0987654321', 1000000);
        echo json_encode($hasil);
    } else {
        echo "API Transfer Saldo";
    }
}
?>