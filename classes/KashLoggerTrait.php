<?php

/**
 * Log table:
 *
 * CREATE TABLE kash_rest_log (id INT PRIMARY KEY AUTO_INCREMENT, class VARCHAR(255) NOT NULL, start_time FLOAT NOT NULL, total_time FLOAT NOT NULL, post MEDIUMTEXT NOT NULL, get MEDIUMTEXT NOT NULL);
 */
trait KashLoggerTrait
{
    protected $kashStartTime;

    protected function startProfiling()
    {
        $this->kashStartTime = microtime(true);
        register_shutdown_function(function () {
            // to run it at the end of list of all shutdown functions
            register_shutdown_function(function () {
                try {
                    $totalTime = microtime(true) - $this->kashStartTime;
                    $config = require _PS_MODULE_DIR_
                        . '/../app/config/parameters.php';
                    $db = mysqli_connect(
                        $config['parameters']['database_host'],
                        $config['parameters']['database_user'],
                        $config['parameters']['database_password'],
                    );
                    if (!$db) {
                        throw new Exception(mysqli_connect_error());
                    }
                    $stmt = mysqli_prepare($db, "INSERT INTO kash_rest_log (class, start_time, total_time, post, get) VALUES (?, ?, ?, ?, ?)");
                    if (!$stmt) {
                        throw new Exception(mysqli_error());
                    }
                    $post = json_encode($_POST);
                    if ($post === false) {
                        throw new Exception(json_last_error_msg());
                    }
                    $get = json_encode($_GET);
                    if ($get === false) {
                        throw new Exception(json_last_error_msg());
                    }
                    if (!mysqli_stmt_bind_param($stmt, basename(self::class), $this->kashStartTime, $totalTime, $post, $get)) {
                        throw new Exception(mysqli_error());
                    }
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception(mysqli_error());
                    }
                } catch (Exception $e) {
                    error_log('[KashLoggerTrait] ' . $e->getMessage());
                }
            });
        });
    }
}
