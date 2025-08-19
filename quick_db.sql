-- Mini ORM Database Schema
-- This file initializes the database with sample tables
USE mini_orm;
-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    age INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_age (age),
    INDEX idx_email (email)
);
-- Posts table
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    user_id INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at)
);
-- Comments table
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id)
);
-- User profiles table (for hasOne relationship)
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    bio TEXT,
    avatar VARCHAR(255),
    website VARCHAR(255),
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
-- Insert sample data
INSERT INTO users (name, email, age, status)
VALUES (
        'Alice Johnson',
        'alice@example.com',
        28,
        'active'
    ),
    ('Bob Wilson', 'bob@example.com', 32, 'active'),
    (
        'Charlie Brown',
        'charlie@example.com',
        24,
        'inactive'
    ),
    (
        'Diana Prince',
        'diana@example.com',
        29,
        'active'
    );
INSERT INTO posts (title, content, user_id, status, published_at)
VALUES (
        'Getting Started with PHP',
        'This is a comprehensive guide to PHP programming...',
        1,
        'published',
        NOW()
    ),
    (
        'Advanced ORM Concepts',
        'Learn about object-relational mapping in depth...',
        1,
        'published',
        NOW()
    ),
    (
        'Database Design Patterns',
        'Best practices for designing robust databases...',
        2,
        'published',
        NOW()
    ),
    (
        'Testing Your Applications',
        'Why testing is crucial for software quality...',
        4,
        'draft',
        NULL
    );
INSERT INTO comments (content, post_id, user_id)
VALUES ('Great article! Very helpful.', 1, 2),
    ('Thanks for sharing this knowledge.', 1, 3),
    (
        'Looking forward to more content like this.',
        2,
        4
    ),
    ('Excellent explanation of ORM concepts.', 2, 2);
INSERT INTO user_profiles (user_id, bio, location)
VALUES (
        1,
        'Software developer passionate about clean code and best practices.',
        'San Francisco, CA'
    ),
    (
        2,
        'Full-stack developer with 10+ years of experience.',
        'New York, NY'
    ),
    (
        4,
        'Tech lead focused on scalable architectures.',
        'London, UK'
    );