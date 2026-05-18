CREATE TABLE questions (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    question_content TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL,
    image_path TEXT
);