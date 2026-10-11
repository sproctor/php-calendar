-- Runs once, when the database volume is first created.
-- Let the app user create and use the test database (app_test).
GRANT ALL PRIVILEGES ON `app\_test`.* TO 'app'@'%';
