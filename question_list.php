<?php
session_start();

// ログイン確認
if (!isset($_SESSION['user_id'])) {
    header("Location: login_3.php");
    exit;
}

/*
 * ボタン押下によるアクションをこの位置で先に処理する
 * (HTML出力前に header() でリダイレクトするため)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'home') {
        header("Location: home.php");
        exit;
    } elseif ($action === 'new_question') {
        header("Location: post_question.php");
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
    } else {
        echo "不明なアクション: $action";
        exit;
    }
}

$host = 'localhost';
$dbname = 'haruki';
$user = 'haruki';
$password_db = '5SXCpdrj';

try {
    // データベース接続
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 質問一覧を取得するクエリ
    $sql = "SELECT q.id, q.subject, q.question_content, q.created_at, q.user_id, u.username
            FROM questions q
            JOIN users u ON q.user_id = u.id
            ORDER BY q.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>質問一覧</title>
    <style>
        /* リセット及び基本設定 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
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

        /* メイン内容のコンテナ */
        .center-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* 質問一覧見出し */
        .questions-title {
            text-align: center;
            font-size: 1.4rem;
            margin-bottom: 20px;
        }

        /* 質問ブロック */
        .question {
            background-color: #fff;
            margin-bottom: 20px;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }

        .question strong a {
            text-decoration: none;
            color: #333;
            font-size: 1.1rem;
        }

        .question p {
            margin-top: 10px;
            line-height: 1.4;
        }

        .question small {
            color: #666;
            display: block;
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .action-links {
            margin-top: 10px;
            font-size: 0.9rem;
        }
        .action-links a {
            color: #0066cc;
            margin-right: 8px;
            text-decoration: underline;
        }
        .action-links a:hover {
            color: #004499;
        }

        .divider {
            border: none;
            border-top: 1px solid #ddd;
            margin-top: 20px;
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

        /* ボタンをまとめるラッパ */
        .button-row {
            text-align: center;
            margin-top: 20px;
        }
        .button-row form {
            display: inline-block;
            margin: 0 8px;
        }

        /* フッター */
        footer {
            text-align: center;
            padding: 16px;
            font-size: 0.85rem;
            color: #666;
        }

        /* レスポンシブ対応 */
        @media (max-width: 767px) {
            .center-container {
                padding: 10px;
            }
            .button-row form {
                display: block;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- ヘッダー -->
    <header>
        <h1>質問一覧</h1>
    </header>

    <!-- メインコンテナ -->
    <div class="center-container">
        <h2 class="questions-title">みんなの質問</h2>

        <?php if (count($questions) > 0): ?>
            <div class="questions">
                <?php foreach ($questions as $question): ?>
                    <div class="question">
                        <strong>
                            <a href="view_question.php?id=<?php echo $question['id']; ?>">
                                <?php echo htmlspecialchars($question['subject'], ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </strong>
                        <p><?php echo nl2br(htmlspecialchars($question['question_content'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <small>
                            投稿者: <?php echo htmlspecialchars($question['username'], ENT_QUOTES, 'UTF-8'); ?> |
                            投稿日: <?php echo date('Y-m-d H:i', strtotime($question['created_at'])); ?>
                        </small>
                        
                        <!-- 編集・削除リンク（投稿者本人の場合のみ） -->
                        <?php if ($_SESSION['user_id'] == $question['user_id']): ?>
                            <div class="action-links">
                                <a href="edit_question.php?id=<?php echo $question['id']; ?>">編集</a>
                                <a href="delete_question.php?id=<?php echo $question['id']; ?>" onclick="return confirm('本当に削除しますか？');">削除</a>
                            </div>
                        <?php endif; ?>
                        <hr class="divider">
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>まだ質問がありません。</p>
        <?php endif; ?>

        <!-- ボタン類 -->
        <div class="button-row">
            <!-- ホームへ戻る -->
            <form method="post" action="question_list.php">
                <button class="btn" type="submit" name="action" value="home">ホームに戻る</button>
            </form>

            <!-- 新しい質問を投稿 -->
            <form method="post" action="question_list.php">
                <button class="btn" type="submit" name="action" value="new_question">新しい質問を投稿</button>
            </form>

            <form method="post" action="question_list.php">
                <button class="btn" type="submit" name="action" value="ask_ai">AIに質問する</button>
            </form>

            <!-- 他の人と勉強する -->
            <form method="post" action="question_list.php">
                <button class="btn" type="submit" name="action" value="study_with_others">他の人と勉強する</button>
            </form>
            <!-- ToDoリスト -->
            <form method="post" action="question_list.php">
                <button class="btn" type="submit" name="action" value="todo">ToDoリスト</button>
            </form>
        </div>
    </div>


</body>
</html>