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
    // データベース接続
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 質問の取得
    if (isset($_GET['id'])) {
        $question_id = (int) $_GET['id'];

        // 修正ポイント: 質問テーブルの image_path も取得する
        $sql = "SELECT q.id, q.subject, q.question_content, q.created_at, q.image_path, u.username
                FROM questions q
                JOIN users u ON q.user_id = u.id
                WHERE q.id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $question_id, PDO::PARAM_INT);
        $stmt->execute();
        $question = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$question) {
            echo "質問が見つかりません。";
            exit;
        }

        // 回答の取得 (画像パスも取得)
        $sql_answers = "SELECT a.answer_content, a.created_at, a.image_path, u.username
                        FROM answers a
                        JOIN users u ON a.user_id = u.id
                        WHERE a.question_id = :question_id
                        ORDER BY a.created_at ASC";
        $stmt_answers = $pdo->prepare($sql_answers);
        $stmt_answers->bindParam(':question_id', $question_id, PDO::PARAM_INT);
        $stmt_answers->execute();
        $answers = $stmt_answers->fetchAll(PDO::FETCH_ASSOC);
    } else {
        echo "質問IDが指定されていません。";
        exit;
    }

    // 回答の投稿処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id = $_SESSION['user_id'];
        $answer_content = $_POST['answer_content'] ?? '';
        $uploadedImagePath = null; // 画像パスをDBに保存するための変数

        // 必須項目チェック
        if ($answer_content) {
            // 画像アップロード処理
            if (isset($_FILES['answer_image']) && $_FILES['answer_image']['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['answer_image']['tmp_name'];
                $originalName = $_FILES['answer_image']['name'];
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $newFileName = time() . '_' . uniqid() . '.' . $extension;

                // アップロード先ディレクトリ
                $uploadDir = __DIR__ . '/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $destination = $uploadDir . '/' . $newFileName;

                if (move_uploaded_file($tmp_name, $destination)) {
                    // DBに保存しやすい形でパスを記録 (例: "uploads/xxxx.jpg")
                    $uploadedImagePath = 'uploads/' . $newFileName;
                }
            }

            // 回答INSERT
            $sql_insert = "INSERT INTO answers (question_id, user_id, answer_content, image_path) 
                           VALUES (:question_id, :user_id, :answer_content, :image_path)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->bindParam(':question_id', $question_id, PDO::PARAM_INT);
            $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt_insert->bindParam(':answer_content', $answer_content, PDO::PARAM_STR);
            $stmt_insert->bindParam(':image_path', $uploadedImagePath, PDO::PARAM_STR);

            if ($stmt_insert->execute()) {
                header("Location: view_question.php?id=" . $question_id);
                exit;
            } else {
                $error_message = "回答の投稿に失敗しました。";
            }
        } else {
            $error_message = "回答内容を入力してください。";
        }
    }
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
    <title>質問詳細</title>
    <!-- login_3.php と同じテイストのデザインを適用 -->
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

        .question-box {
            background-color: #fff;
            width: 100%;
            max-width: 700px;
            padding: 30px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .question-box h2 {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        .question-detail {
            margin-bottom: 30px;
        }
        .question-detail strong {
            font-weight: bold;
        }
        .question-detail p {
            margin: 10px 0;
            line-height: 1.4;
        }
        .question-detail small {
            display: block;
            color: #999;
            margin-top: 8px;
        }

        /* 回答セクション */
        .answer-section {
            margin-bottom: 30px;
        }

        .answer-item {
            border-left: 4px solid #333;
            padding-left: 12px;
            margin-bottom: 20px;
        }
        .answer-item p {
            margin: 5px 0;
            line-height: 1.4;
        }
        .answer-item small {
            color: #999;
        }

        /* 回答投稿フォーム */
        .answer-form textarea {
            width: 100%;
            padding: 10px;
            resize: none;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .answer-form .form-group {
            margin-top: 10px;
        }
        .answer-form button {
            margin-top: 10px;
            padding: 10px 16px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .answer-form button:hover {
            background-color: #444;
        }
        .error-message {
            color: red;
            margin-top: 10px;
        }

        /* 質問一覧に戻るリンク */
        .back-link {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
        }
        .back-link:hover {
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
        <h1>質問詳細</h1>
    </header>

    <!-- メインコンテンツ -->
    <div class="center-container">
        <div class="question-box">
            <h2>質問を確認</h2>

            <!-- 質問内容 -->
            <div class="question-detail">
                <strong>科目:</strong> 
                <?php echo htmlspecialchars($question['subject'], ENT_QUOTES, 'UTF-8'); ?>

                <p><?php echo nl2br(htmlspecialchars($question['question_content'], ENT_QUOTES, 'UTF-8')); ?></p>

                <!-- 画像が投稿されていれば表示する (ここが修正ポイント) -->
                <?php if (!empty($question['image_path'])): ?>
                    <div>
                        <img src="<?php echo htmlspecialchars($question['image_path'], ENT_QUOTES, 'UTF-8'); ?>" 
                             alt="質問画像" 
                             style="max-width: 300px; height: auto; margin-top: 10px;">
                    </div>
                <?php endif; ?>

                <small>
                    投稿者: 
                    <?php echo htmlspecialchars($question['username'], ENT_QUOTES, 'UTF-8'); ?>
                    | 投稿日: 
                    <?php echo date('Y-m-d H:i', strtotime($question['created_at'])); ?>
                </small>
            </div>

            <hr>

            <!-- 回答一覧 -->
            <div class="answer-section">
                <h2>回答一覧</h2>
                <?php if (count($answers) > 0): ?>
                    <?php foreach ($answers as $answer): ?>
                        <div class="answer-item">
                            <p>
                                <?php echo nl2br(htmlspecialchars($answer['answer_content'], ENT_QUOTES, 'UTF-8')); ?>
                            </p>

                            <!-- 画像が投稿されていれば表示する -->
                            <?php if (!empty($answer['image_path'])): ?>
                                <div>
                                    <img src="<?php echo htmlspecialchars($answer['image_path'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="回答画像" 
                                         style="max-width: 300px; height: auto; margin-top: 10px;">
                                </div>
                            <?php endif; ?>

                            <small>
                                投稿者: 
                                <?php echo htmlspecialchars($answer['username'], ENT_QUOTES, 'UTF-8'); ?> 
                                | 投稿日: 
                                <?php echo date('Y-m-d H:i', strtotime($answer['created_at'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>まだ回答がありません。</p>
                <?php endif; ?>
            </div>

            <hr>

            <!-- 回答投稿フォーム 
                 ※必ず enctype="multipart/form-data" を追加 -->
            <div class="answer-form">
                <h2>回答を投稿する</h2>
                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
                <form action="" method="post" enctype="multipart/form-data">
                    <textarea name="answer_content" rows="5" required></textarea>

                    <!-- 画像アップロードフォーム (任意) -->
                    <div class="form-group">
                        <label for="answer_image">画像を添付 (任意):</label>
                        <input type="file" name="answer_image" id="answer_image" accept="image/*">
                    </div>

                    <button type="submit">回答を投稿</button>
                </form>
            </div>

            <!-- 戻るリンク -->
            <a class="back-link" href="question_list.php">質問一覧に戻る</a>
        </div>
    </div>

</body>
</html>