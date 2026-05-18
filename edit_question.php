<?php
session_start();

// ログイン確認
if (!isset($_SESSION['user_id'])) {
    header("Location: login_3.php");
    exit;
}

$host = 'localhost';
$dbname = 'haruki';
$user = 'haruki';
$password_db = '5SXCpdrj';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 質問データ取得
    if (isset($_GET['id'])) {
        $question_id = $_GET['id'];

        $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = :id AND user_id = :user_id");
        $stmt->bindParam(':id', $question_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $question = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$question) {
            echo "権限がありません。";
            exit;
        }
    }

    // 更新処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $subject = $_POST['subject'];
        $question_content = $_POST['question_content'];

        if ($subject && $question_content) {
            $stmt = $pdo->prepare("UPDATE questions SET subject = :subject, question_content = :question_content WHERE id = :id AND user_id = :user_id");
            $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
            $stmt->bindParam(':question_content', $question_content, PDO::PARAM_STR);
            $stmt->bindParam(':id', $question_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                header("Location: question_list.php");
                exit;
            } else {
                $error_message = "更新に失敗しました。";
            }
        } else {
            $error_message = "すべての項目を入力してください。";
        }
    }

} catch (PDOException $e) {
    $error_message = "データベース接続エラー: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>質問編集</title>

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

        /* 中央揃えのコンテナ */
        .center-container {
            flex: 1;
            display: flex;
            justify-content: center; /* 横方向中央 */
            align-items: center;     /* 縦方向中央 */
            padding: 20px;
        }

        /* フォームを囲むボックス */
        .form-box {
            background-color: #fff;
            width: 100%;
            max-width: 500px; /* 横幅をある程度制限 */
            padding: 30px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .form-box h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .error-message {
            color: #e53935;
            margin: 16px 0;
            text-align: center;
            font-weight: bold;
        }

        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: inline-block;
            margin-bottom: 6px;
            color: #666;
        }
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .update-button {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            background-color: #333;
            color: #fff;
            margin-top: 10px;
        }
        .update-button:hover {
            background-color: #444;
        }

        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
        }
        .back-link a {
            color: #337ab7;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
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
        <h1>質問編集</h1>
    </header>

    <!-- メインコンテンツ（中央に配置） -->
    <div class="center-container">
        <div class="form-box">
            <h2>編集フォーム</h2>
            
            <!-- エラーメッセージ -->
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                </div>
            <?php endif; ?>

            <form action="" method="post">
                <div class="form-group">
                    <label for="subject">科目：</label>
                    <input type="text" name="subject" id="subject"
                           value="<?php echo htmlspecialchars($question['subject'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label for="question_content">質問内容：</label>
                    <textarea name="question_content" id="question_content" rows="5"
                              required><?php echo htmlspecialchars($question['question_content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <button class="update-button" type="submit">更新する</button>
                <div class="back-link">
                    <a href="question_list.php">戻る</a>
                </div>
            </form>
        </div>
    </div>

    <!-- フッター -->
    <footer>
        <p>© 2023 MyStudyApp</p>
    </footer>

</body>
</html>