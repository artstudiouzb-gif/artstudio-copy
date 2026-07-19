-- ---------------------------------------------------------------------------
-- Создание таблиц переводов для проектов и членов команды
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS project_translations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id      INT UNSIGNED NOT NULL,
    lang            VARCHAR(8) NOT NULL,
    title           VARCHAR(255) NULL,
    description     LONGTEXT NULL,
    UNIQUE KEY uq_project_translations (project_id, lang),
    CONSTRAINT fk_project_translations_project FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS team_member_translations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    member_id       INT UNSIGNED NOT NULL,
    lang            VARCHAR(8) NOT NULL,
    name            VARCHAR(190) NULL,
    position        VARCHAR(190) NULL,
    UNIQUE KEY uq_team_member_translations (member_id, lang),
    CONSTRAINT fk_team_member_translations_member FOREIGN KEY (member_id) REFERENCES team_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
