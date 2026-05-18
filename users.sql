CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    age INT NOT NULL,
    favorite_subject VARCHAR(50) NOT NULL,
    weak_subject VARCHAR(50) NOT NULL,
    grade VARCHAR(20) NOT NULL   -- 学年カラムを追加
);
