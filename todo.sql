DROP TABLE IF EXISTS todos;

CREATE TABLE todos (
    id SERIAL PRIMARY KEY,          -- 主キー (自動採番)
    user_id INTEGER NOT NULL,       -- ユーザーID
    content TEXT NOT NULL,          -- タスク内容
    completed BOOLEAN NOT NULL DEFAULT FALSE, -- 完了フラグ
    deadline DATE,                  -- 期限(日付)
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP -- 作成日時
);