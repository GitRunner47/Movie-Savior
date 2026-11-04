CREATE TABLE IF NOT EXISTS Images (
    id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    ImageType ENUM('Horizontal', 'Vertical') NOT NULL,
    ImageAddr TEXT NOT NULL
)