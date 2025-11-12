-- Sample Database Schema for Neon Instagres
-- This demonstrates a simple blog-style application schema

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create posts table
CREATE TABLE IF NOT EXISTS posts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    published BOOLEAN DEFAULT false,
    published_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_posts_user_id ON posts(user_id);
CREATE INDEX IF NOT EXISTS idx_posts_published ON posts(published);

-- Insert sample users
INSERT INTO users (username, email) VALUES
    ('alice', 'alice@example.com'),
    ('bob', 'bob@example.com'),
    ('charlie', 'charlie@example.com')
ON CONFLICT (username) DO NOTHING;

-- Insert sample posts
INSERT INTO posts (user_id, title, content, published, published_at) VALUES
    (1, 'Getting Started with Neon', 'Neon makes it easy to create PostgreSQL databases instantly...', true, NOW()),
    (1, 'Building with Serverless Postgres', 'Learn how to scale your application with serverless databases...', true, NOW()),
    (2, 'My First Post', 'This is my first blog post on this platform!', true, NOW()),
    (3, 'Draft Post', 'This post is not yet published.', false, NULL)
ON CONFLICT DO NOTHING;

-- Create a view for published posts with author info
CREATE OR REPLACE VIEW published_posts AS
SELECT 
    p.id,
    p.title,
    p.content,
    p.published_at,
    u.username AS author,
    u.email AS author_email
FROM posts p
JOIN users u ON p.user_id = u.id
WHERE p.published = true
ORDER BY p.published_at DESC;

