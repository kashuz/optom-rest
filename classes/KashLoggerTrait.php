<?php

/**
 * Log table:
 *
 * CREATE TABLE kash_rest_log (id INT PRIMARY KEY AUTO_INCREMENT, class VARCHAR(255) NOT NULL, logged_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, total_time FLOAT NOT NULL, post MEDIUMTEXT NOT NULL, get MEDIUMTEXT NOT NULL);
 */
trait KashLoggerTrait
{
    protected $kashStartTime;

    protected function startProfiling()
    {
        $this->kashStartTime = hrtime(true);
        register_shutdown_function(function () {
            // to run it at the end of list of all shutdown functions
            register_shutdown_function(function () {
                try {
                    $totalTime = (hrtime(true) - $this->kashStartTime) / 1e+9;

                    $config = require _PS_MODULE_DIR_
                        . '/../app/config/parameters.php';

                    $connection = mysqli_connect(
                        $config['parameters']['database_host'],
                        $config['parameters']['database_user'],
                        $config['parameters']['database_password'],
                    );
                    if (!$connection) {
                        throw new Exception(mysqli_connect_error());
                    }

                    if (!mysqli_select_db($connection, $config['parameters']['database_name'])) {
                        throw new Exception(mysqli_error($connection));
                    }

                    $stmt = mysqli_prepare($connection, "INSERT INTO kash_rest_log (class, total_time, post, get) VALUES (?, ?, ?, ?)");
                    if (!$stmt) {
                        throw new Exception(mysqli_error($connection));
                    }

                    $class = basename(get_class($this));
                    $post = json_encode($_POST);
                    if ($post === false) {
                        throw new Exception(json_last_error_msg());
                    }
                    $get = json_encode($_GET);
                    if ($get === false) {
                        throw new Exception(json_last_error_msg());
                    }
                    if (!mysqli_stmt_bind_param($stmt, 'sdss', $class, $totalTime, $post, $get)) {
                        throw new Exception(mysqli_error($connection));
                    }

                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception(mysqli_error($connection));
                    }
                } catch (Exception $e) {
                    error_log('[KashLoggerTrait] ' . $e->getMessage());
                }
            });
        });
    }
}
