<?php
$db = new SQLite3('database.db');

// Создание таблицы Company
$db->exec("CREATE TABLE IF NOT EXISTS Company (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(20) NOT NULL
);");

// Создание таблицы Users
$db->exec("CREATE TABLE IF NOT EXISTS Users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(10) UNIQUE NOT NULL,
    role VARCHAR(8) CHECK(role IN ('manager','executer')) NOT NULL DEFAULT 'executer',
    password VARCHAR(60) UNIQUE NOT NULL,
    view_restrict VARCHAR(3) CHECK(view_restrict IN ('yes','no')) DEFAULT 'no',
    first_name VARCHAR(20),
    last_name VARCHAR(20),
    patronymic VARCHAR(20),
    email VARCHAR(30) UNIQUE,
    company_id INT,
    FOREIGN KEY (company_id) REFERENCES Company(id)
);");

// Создание таблицы Projects
$db->exec("CREATE TABLE IF NOT EXISTS Projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(20) NOT NULL,
    company_id INTEGER,
    FOREIGN KEY (company_id) REFERENCES Company(id) ON DELETE CASCADE
);");

// Создание таблицы Tasks
$db->exec("CREATE TABLE IF NOT EXISTS Tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(20) NOT NULL,
    description VARCHAR NOT NULL,
    importance TEXT CHECK(importance IN ('low', 'medium', 'high')) NOT NULL DEFAULT 'low',
    progress INTEGER CHECK (progress >= 0 AND progress <= 100) NOT NULL,
    deadline DATETIME NOT NULL,
    tag TEXT CHECK(tag IN ('IT', 'Дизайн', 'Маркетинг', 'Аналитика', 'Продажи', 'Копирайтинг', NULL)),
    user_id INTEGER,
    project_id INTEGER,
    file_path TEXT,
    FOREIGN KEY (user_id) REFERENCES Users(id),
    FOREIGN KEY (project_id) REFERENCES Projects(id) ON DELETE CASCADE
);");

// Создание таблицы Checklists
$db->exec("CREATE TABLE IF NOT EXISTS Checklists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    task_id INTEGER NOT NULL,
    item_text VARCHAR(30) NOT NULL,
    is_checked INTEGER DEFAULT 0,
    FOREIGN KEY (task_id) REFERENCES Tasks(id) ON DELETE CASCADE
);");

// Создание таблицы Reviews
$db->exec("CREATE TABLE IF NOT EXISTS Reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    review_text VARCHAR(255) NOT NULL,
    user_id INTEGER,
    FOREIGN KEY (user_id) REFERENCES Users(id)
);");

// Создание таблицы Events
$db->exec("CREATE TABLE IF NOT EXISTS Events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    event_name VARCHAR(20) NOT NULL,
    event_date DATETIME NOT NULL,
    user_id INTEGER,
    recurrence_pattern VARCHAR(20),
    recurrence_end_date DATETIME,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);");

// Создание таблицы Notes
$db->exec("CREATE TABLE IF NOT EXISTS Notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    note_name VARCHAR(20) NOT NULL,
    description VARCHAR,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER,
    FOREIGN KEY (user_id) REFERENCES Users(id) ON DELETE CASCADE
);");
?>