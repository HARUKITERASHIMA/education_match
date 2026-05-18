<?php
session_start();

// ログインチェック
if (!isset($_SESSION['user_id'])) {
    header("Location: login_3.php");
    exit;
}

require_once 'config.php';

// AIへのリクエスト処理（Fetch APIからのPOST）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);
    $question = isset($input['question']) ? trim($input['question']) : '';
    $mode = isset($input['mode']) ? $input['mode'] : 'hint';

    if (empty($question)) {
        echo json_encode(['error' => '問題文を入力してください']);
        exit;
    }

    if ($mode === 'answer') {
        $prompt = "あなたは優秀な家庭教師です。以下の問題に対して、解き方の手順を丁寧に説明しながら、最終的な答えも教えてください。\n\n問題: {$question}";
    } elseif ($mode === 'explain') {
        $prompt = "あなたは優秀な教師です。以下の内容について、背景や関連知識も含めて中学生・高校生にわかりやすく丁寧に説明してください。単なる定義だけでなく、具体的な例や覚え方のコツなども交えて教えてください。\n\n質問: {$question}";
    } else {
        $prompt = "あなたは優秀な家庭教師です。以下の問題に対して、答えは教えずにヒントだけを3つ教えてください。ヒントは段階的に、最初は軽いもの、最後は答えに近いものにしてください。\n\n問題: {$question}";
    }

    $api_key = GEMINI_API_KEY;
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$api_key}";

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        echo json_encode(['error' => 'AIの応答に失敗しました。しばらく待ってから再試行してください。']);
        exit;
    }

    $result = json_decode($response, true);
    $answer = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'AIからの応答を取得できませんでした';

    echo json_encode(['answer' => $answer]);
    exit;
}

$username = isset($_SESSION['username'])
    ? htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8')
    : 'ゲスト';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>AIに質問する</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "Helvetica Neue", Arial, sans-serif;
            background-color: #f9f9f9;
        }

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

        .center-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

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
            color: #666;
        }

        /* ボタンの説明テキスト */
        .mode-description {
            font-size: 0.85rem;
            color: #999;
            margin-top: 12px;
            text-align: left;
            line-height: 1.6;
        }

        textarea {
            width: 100%;
            height: 150px;
            padding: 12px;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            resize: vertical;
            font-family: "Helvetica Neue", Arial, sans-serif;
        }
        textarea:focus {
            outline: none;
            border-color: #333;
        }

        .button-row {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            flex-wrap: wrap;
            justify-content: center;
        }

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
        .btn:disabled {
            background-color: #999;
            cursor: not-allowed;
        }
        .btn-back {
            background-color: #666;
        }
        .btn-back:hover {
            background-color: #777;
        }

        /* AI結果表示エリア */
        .result-box {
            background-color: #fff;
            width: 100%;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            border-radius: 8px;
            display: none;
            text-align: left;
        }
        .result-box.visible {
            display: block;
        }
        .result-title {
            font-size: 1.1rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid #333;
        }
        .result-content {
            font-size: 1rem;
            color: #444;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        /* ローディング */
        .loading {
            color: #666;
            font-size: 1rem;
            margin: 12px 0;
            display: none;
        }
        .loading.visible {
            display: block;
        }

        footer {
            text-align: center;
            padding: 16px;
            font-size: 0.85rem;
            color: #666;
        }
    </style>
</head>
<body>

<header>
    <h1>AIに質問する</h1>
</header>

<div class="center-container">

    <!-- ユーザー名 -->
    <div class="timer-box">
        <div class="timer-label">ようこそ、<?php echo $username; ?>さん</div>
    </div>

    <!-- 問題入力エリア -->
    <div class="timer-box">
        <div class="timer-label">問題文・質問を入力してください</div>
        <textarea id="question" placeholder="例：二次方程式 x²+5x+6=0 を解け&#10;例：関ヶ原の戦いについて教えて&#10;例：現在完了形の使い方がわからない"></textarea>

        <div class="button-row">
            <button class="btn" onclick="askAI('hint')" id="btn-hint">
                💡 ヒントをもらう
            </button>
            <button class="btn" onclick="askAI('answer')" id="btn-answer">
                📖 解き方・答えを見る
            </button>
            <button class="btn" onclick="askAI('explain')" id="btn-explain">
                📚 詳しく教えてもらう
            </button>
        </div>

        <!-- ボタンの使い分け説明 -->
        <div class="mode-description">
            💡 ヒントをもらう：計算・文法問題などで、答えは見ずに自分で解きたいとき<br>
            📖 解き方・答えを見る：計算・文法問題の解説と答えを確認したいとき<br>
            📚 詳しく教えてもらう：歴史・地理・用語など、概念を深く理解したいとき
        </div>

        <!-- ローディング表示 -->
        <div class="loading" id="loading">AIが考えています...</div>
    </div>

    <!-- AI回答表示エリア -->
    <div class="result-box" id="result-box">
        <div class="result-title" id="result-title">AIの回答</div>
        <div class="result-content" id="result-content"></div>
    </div>

    <!-- ホームに戻るボタン -->
    <div class="button-row">
        <a href="home.php">
            <button class="btn btn-back">← ホームに戻る</button>
        </a>
    </div>

</div>

<script>
async function askAI(mode) {
    const question = document.getElementById('question').value.trim();
    if (!question) {
        alert('問題文を入力してください');
        return;
    }

    // ローディング表示・ボタン無効化
    document.getElementById('loading').classList.add('visible');
    document.getElementById('result-box').classList.remove('visible');
    document.getElementById('btn-hint').disabled = true;
    document.getElementById('btn-answer').disabled = true;
    document.getElementById('btn-explain').disabled = true;

    try {
        const response = await fetch('ask_ai.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ question, mode })
        });

        const data = await response.json();

        // 結果タイトルをモードによって変える
        const titles = {
            hint: '💡 ヒント',
            answer: '📖 解き方・答え',
            explain: '📚 詳しい解説'
        };
        document.getElementById('result-title').textContent = titles[mode] || 'AIの回答';
        document.getElementById('result-content').textContent =
            data.answer || data.error || 'エラーが発生しました';
        document.getElementById('result-box').classList.add('visible');

        // 結果までスクロール
        document.getElementById('result-box').scrollIntoView({ behavior: 'smooth' });

    } catch (error) {
        alert('通信エラーが発生しました。再試行してください。');
    } finally {
        // ローディング非表示・ボタン有効化
        document.getElementById('loading').classList.remove('visible');
        document.getElementById('btn-hint').disabled = false;
        document.getElementById('btn-answer').disabled = false;
        document.getElementById('btn-explain').disabled = false;
    }
}
</script>

</body>
</html>