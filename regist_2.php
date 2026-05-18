<?php
// データを処理するパート
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 入力されたデータを取得
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $age = $_POST['age'] ?? '';
    $favorite_subject = $_POST['favorite_subject'] ?? '';
    $weak_subject = $_POST['weak_subject'] ?? '';
    $grade = $_POST['grade'] ?? '';

    // パスワードのハッシュ化
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // データベース接続
    $host = 'localhost';
    $dbname = 'haruki';
    $user = 'haruki';
    $password_db = '5SXCpdrj';

    try {
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // SQL文の作成
        $sql = "INSERT INTO users (username, password, age, favorite_subject, weak_subject, grade) 
                VALUES (:username, :password, :age, :favorite_subject, :weak_subject, :grade)";
        $stmt = $pdo->prepare($sql);

        // パラメータのバインド
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':favorite_subject', $favorite_subject);
        $stmt->bindParam(':weak_subject', $weak_subject);
        $stmt->bindParam(':grade', $grade);

        // 実行
        if ($stmt->execute()) {
            // 登録成功後にlogin_3.phpへリダイレクト
            header('Location: login_3.php');
            exit;
        } else {
            $error_message = "登録に失敗しました。";
        }
    } catch (PDOException $e) {
        $error_message = "データベースエラー: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規登録</title>
    <!-- timer.php と同様のデザインを意識したスタイルを適用 -->
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Helvetica Neue", Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ヘッダー */
        header {
            background-color: #333;
            color: #fff;
            padding: 16px;
            text-align: center;
        }
        header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        /* 中央配置エリア */
        .center-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* 上揃え */
            padding: 20px;
        }

        .box {
            background-color: #fff;
            width: 100%;
            max-width: 500px;
            padding: 30px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .box h2 {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        /* フォーム要素 */
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            margin-bottom: 6px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"],
        input[type="number"],
        select {
            padding: 10px;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        input[type="submit"] {
            align-self: flex-end;
            padding: 10px 16px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #444;
        }

        /* エラーメッセージ */
        .error-message {
            color: red;
            margin-bottom: 10px;
            text-align: center;
        }

        /* フッター */
        footer {
            text-align: center;
            padding: 16px;
            font-size: 0.85rem;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- ヘッダー -->
    <header>
        <h1>新規登録</h1>
    </header>

    <!-- メインコンテンツ -->
    <div class="center-container">
        <div class="box">
            <h2>新規登録フォーム</h2>

            <!-- エラーメッセージ表示 -->
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <!-- 登録フォーム -->
            <form action="regist_2.php" method="post">
                <label for="username">ユーザー名</label>
                <input type="text" id="username" name="username" required>

                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" required>

                <label for="age">年齢</label>
                <input type="number" id="age" name="age" required>

                <label for="grade">学年</label>
                <select id="grade" name="grade" required>
                    <option value="">選択してください</option>
                    <option value="高1">高1</option>
                    <option value="高2">高2</option>
                    <option value="高3">高3</option>
                </select>

                <label for="favorite_subject">得意教科</label>
                <select id="favorite_subject" name="favorite_subject" required>
                    <option value="">選択してください</option>
                    <option value="国語">国語</option>
                    <option value="数学">数学</option>
                    <option value="英語">英語</option>
                    <option value="化学">化学</option>
                    <option value="物理">物理</option>
                    <option value="生物">生物</option>
                    <option value="地理">地理</option>
                    <option value="日本史">日本史</option>
                </select>

                <label for="weak_subject">苦手教科</label>
                <select id="weak_subject" name="weak_subject" required>
                    <option value="">選択してください</option>
                    <option value="国語">国語</option>
                    <option value="数学">数学</option>
                    <option value="英語">英語</option>
                    <option value="化学">化学</option>
                    <option value="物理">物理</option>
                    <option value="生物">生物</option>
                    <option value="地理">地理</option>
                    <option value="日本史">日本史</option>
                </select>

                <input type="submit" value="登録">
            </form>
        </div>
    </div>

</body>
</html>