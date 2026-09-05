<?php declare(strict_types=1);

namespace andy87\knock_knock\tests\helpers;

use RuntimeException;

/** Запускает изолированный PHP HTTP-сервер на loopback для интеграционных тестов. */
final class LocalHttpServer
{
    /** @var resource|null Процесс сервера, принадлежащий этому fixture. */
    private $process = null;

    /** @var string Файл диагностики дочернего процесса. */
    private string $log;

    /** @var int Порт локального сервера. */
    public int $port;

    /**
     * Запускает сервер и ожидает готовности принимать соединения.
     * @return void
     * @throws RuntimeException Если сервер невозможно запустить.
     */
    public function start(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if ($socket === false) {
            throw new RuntimeException($error, $errno);
        }
        $address = stream_socket_get_name($socket, false);
        $this->port = (int)substr(strrchr($address, ':'), 1);
        fclose($socket);

        $log = tempnam(sys_get_temp_dir(), 'knock-knock-');
        if ($log === false) {
            throw new RuntimeException('Cannot create HTTP fixture log');
        }
        $this->log = $log;
        $this->process = proc_open(
            [PHP_BINARY, '-S', $address, __DIR__ . '/../fixtures/http-router.php'],
            [0 => ['pipe', 'r'], 1 => ['file', $this->log, 'a'], 2 => ['file', $this->log, 'a']],
            $pipes,
            null,
            null,
            ['bypass_shell' => true, 'create_no_window' => true]
        );
        if (!is_resource($this->process)) {
            $this->stop();
            throw new RuntimeException('Cannot start HTTP fixture');
        }
        fclose($pipes[0]);

        $deadline = microtime(true) + 5;
        do {
            $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
            if ($connection !== false) {
                fclose($connection);
                return;
            }
            if (!proc_get_status($this->process)['running']) {
                break;
            }
            usleep(10000);
        } while (microtime(true) < $deadline);

        $diagnostics = file_get_contents($this->log);
        $this->stop();
        throw new RuntimeException('HTTP fixture did not start: ' . $diagnostics);
    }

    /**
     * Останавливает только собственный сервер и удаляет его временный лог.
     * @return void
     * Исключения не выбрасываются; очистка допускает повторный вызов.
     */
    public function stop(): void
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
            proc_close($this->process);
            $this->process = null;
        }
        if (isset($this->log) && is_file($this->log)) {
            unlink($this->log);
        }
    }
}
