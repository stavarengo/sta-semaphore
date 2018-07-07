<?php

namespace Sta\Semaphore\Adapter;

use Sta\Semaphore\Adapter\Exception\RuntimeException;

/**
 * Cria semáforos usando as funções nativas do PHP (http://www.php.net/manual/en/book.sem.php).
 * @author Stavarengo
 */
class Native implements SemaphoreAdapterInterface
{

    /**
     * @var Native
     */
    private static $instance;

    /**
     * @return Native
     */
    public static function getInstance(): Native
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function hasNativeSuporte(): bool
    {
        return (function_exists('sem_release') && function_exists('sem_get') && function_exists('sem_acquire'));
    }

    /**
     * @see SemaphoreAdapterInterface::acquire()
     */
    public function acquire(int $key)
    {
        if (!$sem_id = sem_get($key)) {
            throw new RuntimeException("Falha ao adiquirir o ID do semáforo.");
        }
        if (!sem_acquire($sem_id)) {
            throw new RuntimeException("Falha ao adiquirir o semáforo.");
        }
        return $sem_id;
    }

    /**
     * @see SemaphoreAdapterInterface::release()
     */
    public function release($sem_id): void
    {
        if (!sem_release($sem_id)) {
            throw new RuntimeException("Não consegui liberar o semáforo.");
        }
    }

}
