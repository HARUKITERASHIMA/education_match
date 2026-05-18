<?php
session_start();

// ユーザーがログインしているか確認
if (!isset($_SESSION['user_id'])) {
    header("Location: login_3.php");
    exit;
}

// データベース接続設定
$host = 'localhost';
$dbname = 'haruki';
$user = 'haruki';
$password_db = '5SXCpdrj';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 現在のユーザー情報を取得
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT grade, favorite_subject, weak_subject FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_user) {
        echo "ユーザー情報が見つかりません。";
        exit;
    }

    // マッチング条件に合うユーザーを検索
    $stmt = $pdo->prepare("
        SELECT id, grade, favorite_subject, weak_subject
        FROM users
        WHERE grade = :grade
          AND favorite_subject = :weak_subject
          AND weak_subject = :favorite_subject
          AND id != :user_id
    ");
    $stmt->bindParam(':grade', $current_user['grade'], PDO::PARAM_INT);
    $stmt->bindParam(':favorite_subject', $current_user['favorite_subject'], PDO::PARAM_STR);
    $stmt->bindParam(':weak_subject', $current_user['weak_subject'], PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "データベース接続エラー: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>マッチング</title>
    <!-- login_3.php と同じデザインを適用 -->
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

        /* メインコンテンツを中央に配置 */
        .center-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* マッチング情報をまとめるボックス */
        .match-box {
            background-color: #fff;
            width: 100%;
            max-width: 600px;
            padding: 30px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .match-box h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .match {
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 10px;
        }
        .match h3 {
            margin: 0 0 5px;
            font-size: 1.1rem;
        }
        .match p {
            margin: 0 0 5px;
        }

        .no-match {
            text-align: center;
            font-size: 1rem;
            color: #666;
        }

        /* ボタン行 */
        .button-row {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }
        .button-row button {
            padding: 10px;
            font-size: 0.9rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            background-color: #333;
            color: #fff;
        }
        .button-row button:hover {
            background-color: #444;
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
        <h1>マッチング</h1>
    </header>

    <!-- メインコンテンツ -->
    <div class="center-container">
        <div class="match-box">
            <h2>マッチング結果</h2>

            <?php if (!empty($matches)): ?>
                <p>以下のユーザーとマッチしました！</p>
                <?php foreach ($matches as $match): ?>
                    <div class="match">
                        <h3>ユーザーID: <?php echo htmlspecialchars($match['id'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p>学年: <?php echo htmlspecialchars($match['grade'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p>得意教科: <?php echo htmlspecialchars($match['favorite_subject'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <p>苦手教科: <?php echo htmlspecialchars($match['weak_subject'], ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-match">現在、条件に合うユーザーはいません。</p>
            <?php endif; ?>

            <!-- ボタン群 -->
            <div class="button-row">
                <form action="home.php" method="get">
                    <button type="submit">ホームに戻る</button>
                </form>
                <form action="timer.php" method="get">
                    <button type="submit">自習する</button>
                </form>
                <form action="post_question.php" method="get">
                    <button type="submit">他人に質問する</button>
                </form>
                <form action="ask_ai.php" method="get">
                    <button type="submit">AIに質問する</button>
                </form>
                <form action="todo.php" method="get">
                    <button type="submit">ToDoリスト</button>
                </form>
            </div>
        </div>
    </div>


</body>
</html>