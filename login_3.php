<?php
// セッション開始
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 入力されたデータを取得
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // データベース接続情報
    $host = 'localhost';
    $dbname = 'haruki';
    $user = 'haruki';
    $password_db = '5SXCpdrj';

    try {
        // データベース接続
        $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password_db);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // ユーザー名に一致するレコードを検索
        $sql = "SELECT * FROM users WHERE username = :username";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // ユーザー情報を取得
            $userRow = $stmt->fetch(PDO::FETCH_ASSOC);

            // パスワードを照合
            if (password_verify($password, $userRow['password'])) {
                // セッションにユーザー情報を保存
                $_SESSION['user_id'] = $userRow['id'];
                $_SESSION['username'] = $userRow['username'];
                $_SESSION['favorite_subject'] = $userRow['favorite_subject']; // 得意科目
                $_SESSION['weak_subject'] = $userRow['weak_subject'];         // 苦手科目

                // ログイン成功後、home.phpへリダイレクト
                header('Location: home.php');
                exit;  // リダイレクト後は処理を終了
            } else {
                $error = "パスワードが間違っています。";
            }
        } else {
            $error = "ユーザー名が見つかりません。";
        }
    } catch (PDOException $e) {
        $error = "データベース接続エラー: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>勉強集中アプリ</title>
    <!-- timer.php や home.php と同系統のデザインを適用 -->
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
            min-height: 100vh; /* フッターを下部に固定したい場合など */
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

        /* 中央揃えのコンテナ */
        .center-container {
            flex: 1;
            display: flex;
            justify-content: center; /* 横方向中央 */
            align-items: center;     /* 縦方向中央 */
            padding: 20px;
        }

        /* ログインフォームを囲むボックス */
        .login-box {
            background-color: #fff;
            width: 100%;
            max-width: 450px; /* 横幅を制限 */
            padding: 30px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .form-group {
            margin-bottom: 16px;
        }
        label {
            display: inline-block;
            margin-bottom: 6px;
            color: #666;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* エラーメッセージ */
        .error {
            color: #e53935;
            margin: 16px 0;
            text-align: center;
        }

        /* ログインボタン */
        .login-button {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            background-color: #333;
            color: #fff;
        }
        .login-button:hover {
            background-color: #444;
        }

        /* 新規登録リンクなどの補足行 */
        .extra-links {
            margin-top: 20px;
            text-align: center;
        }
        .extra-links a {
            color: #337ab7;
            text-decoration: none;
        }
        .extra-links a:hover {
            text-decoration: underline;
        }

        /* フッター (必要に応じて) */
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
        <h1>勉強集中アプリ</h1>
    </header>

    <!-- メインコンテンツ（中央に配置） -->
    <div class="center-container">
        <div class="login-box">
            <h2>ログインフォーム</h2>
            <!-- エラーメッセージ表示 -->
            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <!-- ログインフォーム -->
            <form action="login_3.php" method="post">
                <div class="form-group">
                    <label for="username">ユーザー名</label>
                    <input type="text" id="username" name="username" required>
                </div>

                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button class="login-button" type="submit">ログイン</button>

                <!-- 新規登録リンク -->
                <div class="extra-links">
                    <p>新規登録はこちらから：<a href="regist_2.php">新規登録</a></p>
                </div>
            </form>
        </div>
    </div>

</body>
</html>