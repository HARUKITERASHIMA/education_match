<?php
session_start();

// ▼ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: login_3.php");
    exit;
}

// DB情報
$host = 'localhost';
$dbname = 'haruki';
$user = 'haruki';
$password_db = '5SXCpdrj';

try {
    // DB接続
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // POSTされた場合の処理
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'post_question') {
            // フォームから受け取る値
            $user_id = $_SESSION['user_id'];
            $subject = $_POST['subject'] ?? '';
            $question_content = $_POST['question_content'] ?? '';

            // 必須項目のチェック
            if ($subject && $question_content) {
                
                // ------------ 画像アップロードの処理 ------------
                $uploadedImagePath = null; // DBに保存するファイルパス用

                if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === UPLOAD_ERR_OK) {
                    // 一時ファイルパス
                    $tmp_name = $_FILES['question_image']['tmp_name'];
                    // 元のファイル名
                    $originalName = $_FILES['question_image']['name'];

                    // ファイルの拡張子を取得 (mime_content_type等でより厳密にチェックしてもOK)
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);

                    // ファイル名をユニークにする (time()とuniqidなどを組合せ)
                    $newFileName = time() . '_' . uniqid() . '.' . $extension;

                    // 保存先ディレクトリ (本ファイルと同じディレクトリ内にある「uploads」）
                    $uploadDir = __DIR__ . '/uploads';

                    // ディレクトリが無い場合は作成 (本番運用では慎重に)
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    // 移動先のフルパス
                    $destination = $uploadDir . '/' . $newFileName;

                    // ファイルを移動
                    if (move_uploaded_file($tmp_name, $destination)) {
                        // Webから参照しやすい形でパスをDBに保存する (例: "uploads/abcdefg.jpg")
                        // ここはサーバ構成に合わせて書き換えて下さい
                        $uploadedImagePath = 'uploads/' . $newFileName;
                    }
                }
                // ------------ 画像アップロードここまで ------------

                // 質問をDBにINSERT
                $sql = "INSERT INTO questions (user_id, subject, question_content, created_at, image_path)
                        VALUES (:user_id, :subject, :question_content, NOW(), :image_path)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
                $stmt->bindParam(':question_content', $question_content, PDO::PARAM_STR);
                $stmt->bindParam(':image_path', $uploadedImagePath, PDO::PARAM_STR);

                if ($stmt->execute()) {
                    $success_message = "質問が投稿されました！";
                } else {
                    $error_message = "質問の投稿に失敗しました。";
                }
            } else {
                $error_message = "すべての項目を入力してください。";
            }
        } elseif ($action === 'home') {
            header("Location: home.php");
            exit;
        } elseif ($action === 'question_list') {
            header("Location: question_list.php");
            exit;
        } elseif ($action === 'ask_ai') {
            header("Location: ask_ai.php");
            exit;
        } elseif ($action === 'study_with_others') {
            header("Location: match.php");
            exit;
        } elseif ($action === 'todo') {
            header("Location: todo.php");
            exit;
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
    <title>質問を投稿する</title>
    <style>
        /* 全体リセットなど */
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

        /* 中央揃えのメインコンテナ */
        .center-container {
            flex: 1;
            display: flex;
            justify-content: center; /* 横方向の中央揃え */
            align-items: center;     /* 縦方向の中央揃え */
            padding: 20px;
        }

        /* 質問投稿用のボックス */
        .post-box {
            background-color: #fff;
            width: 100%;
            max-width: 600px;
            padding: 30px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .post-box h2 {
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        /* フォーム */
        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: inline-block;
            margin-bottom: 6px;
            color: #666;
        }

        select,
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 8px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        select {
            height: 2.5rem;
        }

        textarea {
            resize: vertical;
        }

        /* メッセージエリア */
        .success {
            color: green;
            margin-bottom: 16px;
            text-align: center;
        }
        .error {
            color: #e53935;
            margin-bottom: 16px;
            text-align: center;
        }

        /* ボタン */
        .btn {
            padding: 10px 16px;
            font-size: 1rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            color: #fff;
            background-color: #333;
        }
        .btn:hover {
            background-color: #444;
        }

        /* ボタンの行をまとめる包装用クラス */
        .button-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 20px;
        }

        /* フッター */
        footer {
            text-align: center;
            padding: 16px;
            font-size: 0.85rem;
            color: #666;
        }

        /* レスポンシブ調整 */
        @media (max-width: 767px) {
            .post-box {
                padding: 20px;
            }
            .button-row {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>

    <!-- ヘッダー -->
    <header>
        <h1>質問を投稿する</h1>
    </header>

    <!-- メインコンテンツを中央に配置 -->
    <div class="center-container">
        <div class="post-box">
            <h2>質問内容を入力</h2>

            <!-- 成功メッセージ -->
            <?php if (isset($success_message)): ?>
                <p class="success"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <!-- エラーメッセージ -->
            <?php if (isset($error_message)): ?>
                <p class="error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <!-- 必ず enctype="multipart/form-data" を指定 -->
            <form action="" method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="subject">科目を選択してください:</label>
                    <select name="subject" id="subject" required>
                        <option value="">選択してください</option>
                        <option value="国語">国語</option>
                        <option value="数学">数学</option>
                        <option value="英語">英語</option>
                        <option value="生物">生物</option>
                        <option value="化学">化学</option>
                        <option value="物理">物理</option>
                        <option value="地理">地理</option>
                        <option value="日本史">日本史</option>
                        <option value="世界史">世界史</option>
                        <option value="その他">その他</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="question_content">質問内容:</label>
                    <textarea name="question_content" id="question_content" rows="5" required></textarea>
                </div>

                <!-- 画像添付用のフォーム要素を追加 (任意) -->
                <div class="form-group">
                    <label for="question_image">画像を添付: (任意)</label>
                    <input type="file" name="question_image" id="question_image" accept="image/*">
                </div>

                <button class="btn" type="submit" name="action" value="post_question">投稿する</button>
            </form>

            <!-- 他のページへのリンクボタン -->
            <form action="" method="post" class="button-row">
                <button class="btn" type="submit" name="action" value="home">ホームに戻る</button>
                <button class="btn" type="submit" name="action" value="question_list">質問一覧を見る</button>
                <button class="btn" type="submit" name="action" value="ask_ai">AIに質問する</button>
                <button class="btn" type="submit" name="action" value="study_with_others">他の人と勉強する</button>
                <button class="btn" type="submit" name="action" value="todo">ToDoリスト</button>
            </form>
        </div>
    </div>
</body>
</html>