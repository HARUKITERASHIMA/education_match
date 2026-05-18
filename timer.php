<?php
session_start();

// ▼ ここは各自の環境にあわせて書き換えてください
$dbHost = 'localhost';
$dbName = 'haruki';
$dbUser = 'haruki';
$dbPass = '5SXCpdrj';

// 仮にログイン中のユーザーIDを $_SESSION['user_id'] に格納していると仮定
$userId = $_SESSION['user_id'] ?? null;

// エラーメッセージやサクセスメッセージ用
$errorMessage = '';
$successMessage = '';

// データベース接続
try {
    // PostgreSQL の場合
    $pdo = new PDO("pgsql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    $errorMessage = 'データベース接続失敗: ' . $e->getMessage();
}

// ----------------------------------------------------------------------------------------------
// タイマー開始・終了の操作
// ----------------------------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // タイマー開始
    if (isset($_POST['action']) && $_POST['action'] === 'start_timer') {
        // 開始時刻をセッションに保存
        $_SESSION['start_time'] = date('Y-m-d H:i:s');
        $successMessage = 'タイマーを開始しました。'; // 必要に応じてメッセージ調整

    // タイマー終了
    } elseif (isset($_POST['action']) && $_POST['action'] === 'end_timer') {

        // セッションに開始時間がセットされていない場合
        if (!isset($_SESSION['start_time'])) {
            $errorMessage = 'タイマーが開始されていません。';
        } else {
            // 開始時間と終了時間を取得
            $startTime = $_SESSION['start_time'];
            $endTime   = date('Y-m-d H:i:s');

            // 経過秒数を計算
            $startTimestamp = strtotime($startTime);
            $endTimestamp   = strtotime($endTime);
            $elapsedSeconds = $endTimestamp - $startTimestamp;
            if ($elapsedSeconds < 0) {
                $elapsedSeconds = 0;
            }

            // 勉強内容（トピック）を取得するフォームを別に用意しているならこちらで受け取る
            // 例）<input type="text" name="study_topic">
            // ここではPOSTで受け取っていない想定なので、固定にしておきます
            $studyTopic = ''; // もしくは $_POST['study_topic'] ?? '';

            // DBへ勉強時間を記録する
            // study_times テーブル(id, user_id, start_time, end_time, study_topic, elapsed_seconds)
            // PRIMARY KEY: id
            // FOREIGN KEY (user_id) REFERENCES users(id)
            if ($userId) {
                try {
                    $sql = "INSERT INTO study_times 
                        (user_id, start_time, end_time, study_topic, elapsed_seconds)
                        VALUES (:user_id, :start_time, :end_time, :study_topic, :elapsed_seconds)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
                    $stmt->bindParam(':start_time', $startTime);
                    $stmt->bindParam(':end_time', $endTime);
                    $stmt->bindParam(':study_topic', $studyTopic);
                    $stmt->bindParam(':elapsed_seconds', $elapsedSeconds, PDO::PARAM_INT);
                    $stmt->execute();

                    $successMessage = '勉強時間を記録しました。';
                } catch (PDOException $e) {
                    $errorMessage = '記録の保存に失敗しました: ' . $e->getMessage();
                }
            } else {
                $errorMessage = 'ユーザー情報が取得できません。ログインしてください。';
            }

            // タイマーをリセットして停止状態にする
            unset($_SESSION['start_time']);
        }
    }
}

// ----------------------------------------------------------------------------------------------
// PHP側で表示するための経過時間を計算
// ----------------------------------------------------------------------------------------------
$elapsedTime = 0;
if (isset($_SESSION['start_time'])) {
    $startTimestamp = strtotime($_SESSION['start_time']);
    $currentTimestamp = time();
    $elapsedTime = $currentTimestamp - $startTimestamp;
}
$hours   = floor($elapsedTime / 3600);
$minutes = floor(($elapsedTime % 3600) / 60);
$seconds = $elapsedTime % 60;
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>タイマー</title>
    <style>
        /* 全体のリセットやベーススタイル */
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "Helvetica Neue", Arial, sans-serif;
            background-color: #f9f9f9;
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

        /* ページ内容を中央に詰めるコンテナ */
        .center-container {
            max-width: 600px; 
            margin: 0 auto;    
            padding: 20px;     
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* タイマー表示部分 */
        .timer-box {
            background-color: #fff;
            width: 100%;
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .timer-label {
            font-size: 1.2rem;
            margin-bottom: 10px;
        }
        .timer-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        /* ボタン行 */
        .button-row {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .button-row form {
            display: inline-block;
        }

        /* ボタン共通デザイン */
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

        /* メッセージ表示 */
        .message {
            margin: 10px 0;
            text-align: center;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
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
        <h1>タイマー</h1>
    </header>

    <!-- コンテンツを中央に集めるメインコンテナ -->
    <div class="center-container">

        <!-- メッセージ(エラー・サクセス) -->
        <?php if($errorMessage !== ''): ?>
            <div class="message error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if($successMessage !== ''): ?>
            <div class="message success"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <!-- タイマーの表示枠 -->
        <div class="timer-box">
            <div class="timer-label">勉強時間:</div>
            <p class="timer-value" id="timerDisplay">
                <?php echo sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds); ?>
            </p>
        </div>

        <!-- タイマー開始・終了ボタン -->
        <div class="button-row">
            <form action="timer.php" method="post">
                <button class="btn" type="submit" name="action" value="start_timer">タイマーを開始</button>
            </form>
            <form action="timer.php" method="post">
                <button class="btn" type="submit" name="action" value="end_timer">タイマーを終了</button>
            </form>
        </div>

        <!-- 他ページへの遷移ボタン（例） -->
        <div class="button-row">
            <form action="home.php" method="get">
                <button class="btn" type="submit">ホームに戻る</button>
            </form>
            <form action="post_question.php" method="get">
                <button class="btn" type="submit">他人に質問する</button>
            </form>
            <form action="ask_ai.php" method="get">
                <button class="btn" type="submit">AIに質問する</button>
            </form>
            <form action="match.php" method="get">
                <button class="btn" type="submit">他の人と勉強する</button>
            </form>
            <form action="todo.php" method="get">
                <button class="btn" type="submit">ToDoリスト</button>
        </div>
    </div>

    <!-- JavaScriptでリアルタイムタイマーも動かす -->
    <script>
        var timerInterval;
        // PHPのセッションに開始時間があれば(UNIXタイムとして)、JavaScriptでカウントを開始
        var startTime = <?php echo isset($_SESSION['start_time']) ? strtotime($_SESSION['start_time']) : 0; ?>;

        if (startTime) {
            timerInterval = setInterval(function() {
                var currentTime = Math.floor(Date.now() / 1000);
                var elapsedTime = currentTime - startTime;

                if (elapsedTime < 0) {
                    elapsedTime = 0;
                }

                var hours = Math.floor(elapsedTime / 3600);
                var minutes = Math.floor((elapsedTime % 3600) / 60);
                var seconds = elapsedTime % 60;

                document.getElementById('timerDisplay').innerText =
                    String(hours).padStart(2, '0') + ":" +
                    String(minutes).padStart(2, '0') + ":" +
                    String(seconds).padStart(2, '0');
            }, 1000);
        }
    </script>
</body>
</html>