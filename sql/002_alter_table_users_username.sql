ALTER TABLE Users
  ADD COLUMN username VARCHAR(30)
    NOT NULL
    UNIQUE
    DEFAULT (
      LEFT(CONCAT(SUBSTRING_INDEX(email, '@', 1), '-', MD5(email)), 30)
    )
    COMMENT 'Username defaults to the email local part plus hash, truncated to 30 chars'
    AFTER email;