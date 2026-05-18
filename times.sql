-- study_times テーブルの作成
CREATE TABLE study_times (
    id SERIAL PRIMARY KEY,                       -- ID (自動採番)
    user_id INTEGER NOT NULL,                    -- ユーザーID
    start_time TIMESTAMP NOT NULL,               -- 勉強開始時間
    end_time TIMESTAMP,                          -- 勉強終了時間（NULLを許容）
    study_topic VARCHAR(255),                    -- 勉強内容
    elapsed_seconds INT,                         -- 勉強時間（秒）
    FOREIGN KEY (user_id) REFERENCES users(id)   -- ユーザーIDの外部キー制約
);
