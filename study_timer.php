<?php
// エラーハンドリングを有効化
ini_set('display_errors', 1);
error_reporting(E_ALL);

// データベース接続設定
$host = 'localhost'; // データベースのホスト
$dbname = 'your_database_name'; // データベース名
$user = 'your_username'; // ユーザー名
$password = 'your_password'; // パスワード

// リクエストからJSONデータを取得
$data = json_decode(file_get_contents('php://input'), true);

// 送信されたデータが正しいか確認
if (isset($data['start_time']) && isset($data['end_time']) && isset($data['elapsed_time'])) {
    // データベースに接続
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 勉強時間データをデータベースに挿入
        $stmt = $pdo->prepare("INSERT INTO study_times (start_time, end_time, elapsed_time) VALUES (:start_time, :end_time, :elapsed_time)");
        $stmt->bindParam(':start_time', $data['start_time']);
        $stmt->bindParam(':end_time', $data['end_time']);
        $stmt->bindParam(':elapsed_time', $data['elapsed_time']);

        // 実行して、データが正常に保存されるか確認
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'elapsed_time' => $data['elapsed_time']]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'データベースへの挿入に失敗しました']);
        }

    } catch (PDOException $e) {
        // PDOエラーを捕捉して詳細なエラーメッセージを表示
        echo json_encode(['status' => 'error', 'message' => 'PDOエラー: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => '無効なデータ']);
}
?>
