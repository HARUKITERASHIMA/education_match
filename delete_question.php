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

    // 質問IDを取得
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("有効な質問IDが指定されていません。");
    }
    $question_id = (int)$_GET['id'];

    // トランザクション開始
    $pdo->beginTransaction();

    // 1. 関連する回答を削除
    $deleteAnswersSql = "DELETE FROM answers WHERE question_id = :question_id";
    $stmt = $pdo->prepare($deleteAnswersSql);
    $stmt->bindParam(':question_id', $question_id, PDO::PARAM_INT);
    $stmt->execute();

    // 2. 質問を削除
    $deleteQuestionSql = "DELETE FROM questions WHERE id = :question_id";
    $stmt = $pdo->prepare($deleteQuestionSql);
    $stmt->bindParam(':question_id', $question_id, PDO::PARAM_INT);
    $stmt->execute();

    // トランザクションを確定
    $pdo->commit();

    // 成功メッセージをセッションに保存
    $_SESSION['success_message'] = "質問が正常に削除されました。";
    header("Location: question_list.php");
    exit;

} catch (PDOException $e) {
    // エラー時にロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "データベース接続エラー: " . $e->getMessage();
    exit;
} catch (Exception $e) {
    // その他のエラー時もロールバック
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "エラー: " . $e->getMessage();
    exit;
}
?>
