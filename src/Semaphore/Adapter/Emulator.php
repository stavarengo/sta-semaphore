<?php

namespace Sta\Semaphore\Adapter;

use Sta\Semaphore\Adapter\Exception\RuntimeException;

/**
 * Emula o comportamento das funções de semáforo nativas do PHP (http://www.php.net/manual/en/book.sem.php).
 * @author Stavarengo
 */
class Emulator implements SemaphoreAdapterInterface
{
    /**
     * @var array[]
     */
    private static $semaphores = [];
    /**
     * @var string[]
     */
    private static $semaphoresHash = [];
    /**
     * @var Emulator
     */
    private static $instance;
    /**
     * @var string
     */
    protected $path;

    private function __construct()
    {
        $this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'semaphores';
        if (!file_exists($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    /**
     * @return Emulator
     */
    public static function getInstance(): Emulator
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @see SemaphoreAdapterInterface::acquire()
     */
    public function acquire(int $key)
    {
        $inicio   = time();
        $fileName = $this->path . DIRECTORY_SEPARATOR . $this->_getHash((string)$key);

        $fp = false;
        do {
            // Gera o código usada para garantir que somente a mesma função que chamou este método poderá
            // liberar o semaforo.
            $code = microtime() . mt_rand();
            if (!file_exists($fileName)) {
                $fp = @fopen($fileName, 'x');
                if ($fp) {
                    $content = file_get_contents($fileName);
                    if (empty($content)) {
                        file_put_contents($fileName, $code);
                    }
                    $content = file_get_contents($fileName);
                    if ($content != $code) {
                        @fclose($fp);
                        $fp = false;
                    }
                }
            }
            if (!$fp) {
                usleep(500000);
                $tempoDeEspera = time() - $inicio;
                if ($tempoDeEspera > 15) {
                    //mais de 15 segundos esperando
                    throw new RuntimeException("Falha ao adiquirir o semáforo.");
                }
            }
        } while (!$fp);

        self::$semaphores[$key] = [
            'resource' => $fp,
            'fileName' => $fileName,
            'code' => $code
        ];

        return $this->_createSemaphore($key, $code);
    }

    /**
     * @see SemaphoreAdapterInterface::release()
     */
    public function release($semaphore): void
    {
        if (!is_array($semaphore) || !isset($semaphore['code']) || !isset($semaphore['key'])) {
            return;
        }

        $key = $semaphore['key'];
        if (isset(self::$semaphores[$key]) && self::$semaphores[$key] !== false) {
            $semaphore = self::$semaphores[$key];
            $code      = $semaphore['code'];

            //Se os códigos não forem iguais significa que a função que está chamando este método não
            //é a função que bloqueou o semaforo
            if ($code == $semaphore['code']) {
                $fileName = $semaphore['fileName'];
                $content  = file_get_contents($fileName);
                if ($content == $code) {
                    self::$semaphores[$key] = false;
                    fclose($semaphore['resource']);
                    if (!unlink($fileName)) {
                        throw new RuntimeException("Falha ao liberar o semáforo.");
                    }
                }
            }
        }
    }

    private function _getHash(string $key): string
    {
        if (!isset(self::$semaphoresHash[$key])) {
            self::$semaphoresHash[$key] = md5($key);
        }
        return self::$semaphoresHash[$key];
    }

    private function _createSemaphore(int $key, string $code): array
    {
        return ['code' => $code, 'key' => $key];
    }

}
