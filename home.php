<?php
session_start();

// ユーザーがログインしていない場合、ログイン画面にリダイレクト
if (!isset($_SESSION['user_id'])) {
    header("Location: login_3.php");
    exit;
}

// DB接続情報
$host = 'localhost';
$dbname = 'haruki';
$user = 'haruki';
$password_db = '5SXCpdrj';

// ランダムメッセージ用の配列
$encouragementMessages = [
    "素晴らしい成果です！この調子で続けましょう！",
    "今日も勉強頑張ろう！",
    "質も量もこなそう！"
];

// POSTリクエストを受け取った場合の処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action'] ?? '') {
        case 'self_study':
            header("Location: timer.php");
            exit;
        case 'ask_question':
            header("Location: post_question.php");
            exit;
        case 'ask_ai':
            header("Location: ask_ai.php");
            exit;
        case 'study_with_others':
            header("Location: match.php");
            exit;
        case 'todo':
            header("Location: todo.php");
            exit;
        case 'logout':
            session_destroy();
            header("Location: login_3.php");
            exit;
        default:
            header("Location: home.php");
            exit;
    }
}

try {
    // PDO生成
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $user, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ユーザーID
    $user_id = $_SESSION['user_id'];

    // ランダムメッセージを一つ選ぶ
    $randomKey = array_rand($encouragementMessages);
    $currentMessage = $encouragementMessages[$randomKey];

    // ------------------- 勉強時間集計用の関数群 -------------------
    function getDailyStudyTime($pdo, $user_id) {
        $today = date('Y-m-d');
        $sql = "SELECT SUM(elapsed_seconds) AS total_seconds
                FROM study_times
                WHERE user_id = :user_id
                  AND DATE(start_time) = :today";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':today', $today);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_seconds'] ?: 0;
    }

    function getWeeklyStudyTime($pdo, $user_id) {
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $weekEnd   = date('Y-m-d', strtotime('sunday this week'));
        $sql = "SELECT SUM(elapsed_seconds) AS total_seconds
                FROM study_times
                WHERE user_id = :user_id
                  AND DATE(start_time) BETWEEN :weekStart AND :weekEnd";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':weekStart', $weekStart);
        $stmt->bindParam(':weekEnd', $weekEnd);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_seconds'] ?: 0;
    }

    function getTotalStudyTime($pdo, $user_id) {
        $sql = "SELECT SUM(elapsed_seconds) AS total_seconds
                FROM study_times
                WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_seconds'] ?: 0;
    }

    function getMonthlyStudyData($pdo, $user_id) {
        $sql = "SELECT DATE_TRUNC('month', start_time) AS month,
                       SUM(elapsed_seconds) AS total_seconds
                FROM study_times
                WHERE user_id = :user_id
                GROUP BY month
                ORDER BY month";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // ----------------------------------------------------------

    // 勉強時間を取得
    $dailyStudyTime  = getDailyStudyTime($pdo, $user_id);
    $weeklyStudyTime = getWeeklyStudyTime($pdo, $user_id);
    $totalStudyTime  = getTotalStudyTime($pdo, $user_id);

    // 月毎の勉強データ
    $monthlyStudyData = getMonthlyStudyData($pdo, $user_id);
    $monthlyLabels = [];
    $monthlyValues = [];
    foreach ($monthlyStudyData as $row) {
        $monthlyLabels[] = date('Y-m', strtotime($row['month']));
        // 秒から時間へ変換 (小数点第2位まで)
        $monthlyValues[] = round($row['total_seconds'] / 3600, 2);
    }

    // ユーザー名取得
    $username = isset($_SESSION['username'])
                ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8')
                : 'ゲスト';

} catch (PDOException $e) {
    echo "データベース接続エラー: " . $e->getMessage();
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>ホーム</title>
    <!-- Chart.js (月ごと勉強時間の表示用) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- timer.php と同じデザインを再現するCSS -->
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

        /* 中央揃えのコンテナ */
        .center-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        /* 内容を囲むボックス */
        .timer-box {
            background-color: #fff;
            width: 100%;
            text-align: center;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        /* 小見出し */
        .timer-label {
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #666;
        }
        /* 勉強時間などの表示 */
        .timer-value {
            font-size: 1.25rem;
            font-weight: bold;
            color: #333;
            margin: 8px 0;
        }

        /* メッセージ表示（赤文字） */
        .encouragement-message {
            color: #e53935;
            font-weight: bold;
            margin: 1rem 0 0;
        }

        /* ボタンの行 */
        .button-row {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .button-row form {
            display: inline-block;
        }

        /* timer.php と同じボタンデザイン */
        .btn {
            padding: 10px 16px;
            font-size: 1rem;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            color: #fff;
            background-color: #333; /* timer.phpと同じ背景色 */
        }
        .btn:hover {
            background-color: #444; /* hover時の色もtimer.phpと合わせる */
        }

        /*
        ---------------------------------------------------
        もし「自習/質問/他の人と勉強/ログアウト」で
        ボタンの色を変えたい場合は、以下の色分けを
        復活させてもOKですが、その場合タイマー画面と
        完全に同じ色合いにはなりません。
        ---------------------------------------------------
        .btn-self-study {
            background-color: #4caf50;
        }
        .btn-ask-question {
            background-color: #f39c12;
        }
        .btn-study-with-others {
            background-color: #9b59b6;
        }
        .btn-logout {
            background-color: #e74c3c;
        }
        */

        /* グラフ領域（Chart.js） */
        .chart-wrapper {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
            width: 100%;
            text-align: center;
        }
        .chart-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #666;
        }

        /* フッター（必要なら） */
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
        <h1>ホーム</h1>
    </header>

    <!-- 中央コンテナ -->
    <div class="center-container">

        <!-- ユーザー名とメッセージを同じボックス内に表示 -->
        <div class="timer-box">
            <div class="timer-label">ようこそ、<?php echo $username; ?>さん</div>
            
            <!-- 応援メッセージ（赤文字） -->
            <div class="encouragement-message">
                <?php echo htmlspecialchars($currentMessage, ENT_QUOTES, 'UTF-8'); ?>
            </div>
        </div>

        <!-- 今日・今週・合計勉強時間表示 -->
        <div class="timer-box">
            <div class="timer-label">今日の勉強時間</div>
            <div class="timer-value">
                <?php echo gmdate("H:i:s", $dailyStudyTime); ?>
            </div>
            <div class="timer-label">今週の勉強時間</div>
            <div class="timer-value">
                <?php echo gmdate("H:i:s", $weeklyStudyTime); ?>
            </div>
            <div class="timer-label">これまでの合計勉強時間</div>
            <div class="timer-value">
                <?php echo gmdate("H:i:s", $totalStudyTime); ?>
            </div>
        </div>

        <!-- 月ごとの学習時間グラフ -->
        <div class="chart-wrapper">
            <div class="chart-title">月ごとの勉強時間 (時間)</div>
            <canvas id="monthlyStudyChart"></canvas>
        </div>

        <!-- ボタン群（自習開始・質問する・他の人と勉強・ToDoリスト・ログアウト） -->
        <div class="button-row">
            <form action="home.php" method="post">
                <button type="submit" name="action" value="self_study" class="btn">
                    自習する
                </button>
            </form>

            <form action="home.php" method="post">
                <button type="submit" name="action" value="ask_question" class="btn">
                    他人に質問する
                </button>
            </form>
            <form action="home.php" method="post">
                <button type="submit" name="action" value="ask_ai" class="btn">
                    AIに質問する
                </button>
            </form>

            <form action="home.php" method="post">
                <button type="submit" name="action" value="study_with_others" class="btn">
                    他の人と勉強する
                </button>
            </form>

            <!-- ToDoリストボタンを「他の人と勉強する」の隣に配置 -->
            <form action="home.php" method="post">
                <button type="submit" name="action" value="todo" class="btn">
                    ToDoリスト
                </button>
            </form>

            <form action="home.php" method="post">
                <button type="submit" name="action" value="logout" class="btn">
                    ログアウト
                </button>
            </form>
        </div>

    </div><!-- /.center-container -->

    <!-- Chart.js のスクリプト -->
    <script>
        // ==============================================================
        // カスタムプラグイン (Y 軸タイトルを「横向き」で表示する例)
        // ==============================================================
        const yTitleHorizontalPlugin = {
            id: 'y-title-horizontal',
            afterDraw(chart) {
                const yScale = chart.scales.y;
                if (!yScale) {
                    return; 
                }
                
                const ctx = chart.ctx;
                ctx.save();

                const xOffset = 30;
                const xPos = yScale.left - xOffset;
                const yPos = (yScale.top + yScale.bottom) / 2;

                ctx.translate(xPos, yPos);
                ctx.rotate(0);

                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = '14px sans-serif';
                ctx.fillStyle = '#666';

                ctx.fillText('時間 (h)', 0, 0);
                ctx.restore();
            }
        };

        // ===================================
        // Chart.js本体の初期化
        // ===================================
        const ctx = document.getElementById('monthlyStudyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($monthlyLabels); ?>,
                datasets: [{
                    label: '勉強時間 (h)',
                    data: <?php echo json_encode($monthlyValues); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            plugins: [yTitleHorizontalPlugin],
            options: {
                layout: {
                    padding: {
                        left: 70
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: '月'
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        title: {
                            display: false
                        },
                        beginAtZero: true,
                        min: 0,
                        max: 10,
                        ticks: {
                            stepSize: 0.5
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>