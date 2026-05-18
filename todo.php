<?php
session_start();

// ▼ログインしていない場合の処理 (必要に応じて書き換えてください)
if (!isset($_SESSION['user_id'])) {
    // header("Location: login.php");
    // exit;
    // サンプルのためコメントアウト
}

// --- DB接続情報（PostgreSQL想定）---
$host = 'localhost';
$dbname = 'haruki';
$dbuser = 'haruki';
$dbpassword = '5SXCpdrj';

try {
    // "mysql:host=$host;dbname=$dbname" に書き換えればMySQLでもOK
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $dbuser, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    echo "データベース接続エラー: " . $e->getMessage();
    exit;
}

// ログインしているユーザーのID (サンプルのため1を仮定)
$user_id = $_SESSION['user_id'] ?? 1;

// --------------------------------------------------
// 1) 新規タスクの追加
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_task'])) {
    $taskContent = trim($_POST['new_task']);
    // 期限は任意入力(日付)
    $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

    if ($taskContent !== '') {
        $insertSql = "INSERT INTO todos (user_id, content, deadline) 
                      VALUES (:user_id, :content, :deadline)";
        $stmt = $pdo->prepare($insertSql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':content', $taskContent, PDO::PARAM_STR);

        if ($deadline === null) {
            $stmt->bindValue(':deadline', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindParam(':deadline', $deadline, PDO::PARAM_STR);
        }

        $stmt->execute();
    }

    // 二重送信対策
    header("Location: todo.php");
    exit;
}

// --------------------------------------------------
// 2) タスク完了状態の更新 (チェックボックス切り替え)
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'], $_POST['toggle_completed'])) {
    $taskId = (int)$_POST['task_id'];
    // 1なら完了に、0なら未完了に戻す
    $newCompleted = ($_POST['toggle_completed'] === '1') ? 'true' : 'false';

    // まず、現在のタスク情報を取得
    $selectSql = "SELECT completed, deadline FROM todos 
                  WHERE id = :id AND user_id = :user_id";
    $stmtSelect = $pdo->prepare($selectSql);
    $stmtSelect->bindParam(':id', $taskId, PDO::PARAM_INT);
    $stmtSelect->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmtSelect->execute();
    $taskInfo = $stmtSelect->fetch(PDO::FETCH_ASSOC);

    if (!$taskInfo) {
        // 該当タスクなし
        header("Location: todo.php");
        exit;
    }

    // 期限チェック用に現在日付を取得 (PHPの日付)
    $today = date('Y-m-d'); // 時刻さえ不要なら date('Y-m-d')で十分
    $deadline = $taskInfo['deadline'];

    // 今の完了状態
    $currentCompleted = $taskInfo['completed'];

    // 「未完了に戻す」操作のときだけ、期限を過ぎていないかどうかチェック
    // newCompleted が 'false' => ユーザーはチェックを外そうとしている
    if ($newCompleted === 'false') {
        // 期限が設定されていて、かつ今日が期限日以降(期限切れ)なら、更新を許可しない
        if ($deadline && $today > $deadline) {
            // 期限を過ぎているので、未完了に戻せない → 何も更新せずにリダイレクト
            header("Location: todo.php");
            exit;
        }
    }

    // 上記のチェックを通ったら、完了状態を更新する
    $updateSql = "UPDATE todos SET completed = :completed 
                  WHERE id = :id AND user_id = :user_id";
    $stmtUpdate = $pdo->prepare($updateSql);
    $stmtUpdate->bindValue(':completed', $newCompleted, PDO::PARAM_BOOL);
    $stmtUpdate->bindParam(':id', $taskId, PDO::PARAM_INT);
    $stmtUpdate->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmtUpdate->execute();

    header("Location: todo.php");
    exit;
}

// --------------------------------------------------
// 3) タスクの削除
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = (int)$_POST['delete_id'];

    $deleteSql = "DELETE FROM todos WHERE id = :id AND user_id = :user_id";
    $stmtDelete = $pdo->prepare($deleteSql);
    $stmtDelete->bindParam(':id', $deleteId, PDO::PARAM_INT);
    $stmtDelete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmtDelete->execute();

    header("Location: todo.php");
    exit;
}

// --------------------------------------------------
// 4) タスク一覧の取得
// --------------------------------------------------
$listSql = "SELECT id, content, completed, deadline, created_at
            FROM todos
            WHERE user_id = :user_id
            ORDER BY id DESC";
$stmtList = $pdo->prepare($listSql);
$stmtList->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmtList->execute();
$tasks = $stmtList->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>期限付きToDoリスト（再チェック制限）</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0; padding: 0;
            font-family: "Helvetica Neue", Arial, sans-serif;
            background-color: #f9f9f9;
        }
        header {
            background-color: #333; color: #fff;
            padding: 16px; text-align: center;
        }
        header h1 { margin: 0; font-size: 1.8rem; }
        .center-container {
            max-width: 600px; margin: 0 auto; padding: 20px;
        }
        .todo-container {
            background-color: #fff; padding: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border-radius: 8px; margin-bottom: 20px;
        }
        .todo-title {
            font-size: 1.2rem; margin-bottom: 10px;
            color: #333; text-align: center;
        }
        .todo-form {
            text-align: center; margin-bottom: 16px;
        }
        .todo-form input[type="text"],
        .todo-form input[type="date"] {
            padding: 8px; font-size: 1rem; margin: 4px;
        }
        .todo-form input[type="text"] { width: 45%; }
        .todo-form input[type="date"] { width: 35%; }
        .btn {
            padding: 10px 16px; font-size: 1rem; border-radius: 4px;
            border: none; cursor: pointer; color: #fff; background-color: #333;
        }
        .btn:hover { background-color: #444; }
        .todo-list {
            list-style: none; padding: 0; margin: 0;
        }
        .todo-item {
            display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid #ddd; padding: 8px 0;
        }
        .todo-item:last-child {
            border-bottom: none;
        }
        .todo-text {
            display: flex; align-items: center; flex: 1;
        }
        .todo-text input[type="checkbox"] {
            margin-right: 8px;
        }
        .content {
            margin-right: 8px;
        }
        .completed .content {
            text-decoration: line-through;
            color: #aaa;
        }
        .todo-deadline {
            font-size: 0.9rem; color: #777;
        }
        .todo-actions form { display: inline; }

        .button-row {
            display: flex; gap: 10px; margin: 0 auto;
            justify-content: center; flex-wrap: wrap;
        }
        .button-row form { display: inline-block; }
    </style>
</head>
<body>
    <header>
        <h1>ToDoリスト</h1>
    </header>

    <div class="center-container">
        <!-- ToDo部分 -->
        <div class="todo-container">
            <div class="todo-title">やることリスト</div>

            <!-- 新規追加フォーム -->
            <form class="todo-form" action="todo.php" method="post">
                <input type="text" name="new_task" placeholder="やること" required>
                <input type="date" name="deadline" placeholder="YYYY-MM-DD">
                <button class="btn" type="submit">追加</button>
            </form>

            <!-- 一覧表示 -->
            <ul class="todo-list">
                <?php if (!empty($tasks)): ?>
                    <?php foreach ($tasks as $task): ?>
                        <li class="todo-item">
                            <!-- 左側：checkbox + 内容 + 期限 -->
                            <div class="todo-text <?php echo $task['completed'] ? 'completed' : ''; ?>">
                                <!-- 完了チェックボックス -->
                                <form action="todo.php" method="post" style="margin: 0;">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <input type="checkbox"
                                           name="toggle_completed"
                                           value="<?php echo $task['completed'] ? '0' : '1'; ?>"
                                           onchange="this.form.submit()"
                                           <?php echo $task['completed'] ? 'checked' : ''; ?>>
                                </form>

                                <span class="content">
                                    <?php echo htmlspecialchars($task['content'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>

                                <?php if ($task['deadline']): ?>
                                    <span class="todo-deadline">（期限: <?php echo htmlspecialchars($task['deadline'], ENT_QUOTES, 'UTF-8'); ?>）</span>
                                <?php endif; ?>
                            </div>

                            <!-- 削除ボタン -->
                            <div class="todo-actions">
                                <form action="todo.php" method="post" onsubmit="return confirm('削除しますか？');">
                                    <input type="hidden" name="delete_id" value="<?php echo $task['id']; ?>">
                                    <button class="btn" type="submit">削除</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>まだやることはありません。</li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- ページ下部のナビゲーション -->
        <div class="button-row">
            <!-- ホーム -->
            <form action="home.php" method="get">
                <button class="btn" type="submit">ホームに戻る</button>
            </form>
            <!-- 自習 -->
            <form action="timer.php" method="get">
                <button class="btn" type="submit">自習をする</button>
            </form>
            <!-- 質問 -->
            <form action="post_question.php" method="get">
                <button class="btn" type="submit">質問する</button>
            </form>
            <!-- マッチング -->
            <form action="match.php" method="get">
                <button class="btn" type="submit">他の人と勉強する</button>
            </form>
        </div>
    </div>
</body>
</html>